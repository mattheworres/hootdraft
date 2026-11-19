import { Page } from '@playwright/test';
import { ApiSetup, Draft, DraftOptions, LoggedInUser, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { failApiRequest } from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { setDraftCreateTime } from '../helpers/timestamps';
import { expectToast } from '../helpers/toast';

// Route group 5E of docs/flow-inventory.md. Each test name starts with the row ID.
//
// The Draft table lists each Draft in the database, and tests run at the same time. Each test names its Drafts
// with a tag that no other test uses and searches for the tag, so only its own Drafts show.

const NO_DRAFTS = 'No drafts here, chief.';
const PAGE_SIZE = 15;
const COLUMNS = ['League Name', 'Sport', 'Commissioner', 'Created', 'Status'];

const nameSearch = (page: Page) => page.getByRole('textbox', { name: 'Search Leagues...' });
// The table holds one Commissioner filter for each screen width, and only one is visible
const commissionerSearch = (page: Page) =>
  page.getByRole('textbox', { name: 'Search Commissioners...' }).filter({ visible: true });
const draftRows = (page: Page) => page.getByTestId('draft-row');
const draftLinks = (page: Page) => draftRows(page).getByRole('link');
// The sort icons are part of the accessible name of a header
const header = (page: Page, name: string) => page.getByRole('columnheader', { name: new RegExp(`${name}$`) });
const commissionerLabel = (page: Page) => page.getByTestId('commissioner-filter-label').filter({ visible: true });
const resetButton = (page: Page) => page.getByRole('button', { name: 'Reset All Filters' });
const noDraftsHeading = (page: Page) => page.getByRole('heading', { name: NO_DRAFTS });
const createDraftLink = (page: Page) => page.getByRole('link', { name: 'Create your own draft!' });
const pager = (page: Page) => page.getByTestId('draft-pager');
const filterDropdown = (page: Page, filter: 'sport' | 'status') => page.getByTestId(`${filter}-filter`);
const filterButton = (page: Page, filter: 'sport' | 'status') => filterDropdown(page, filter).getByRole('button');
const lockIcon = (page: Page, draft: Draft) =>
  draftRows(page).filter({ hasText: draft.name }).getByTestId('draft-lock-icon');

const tagged = (tag: string, suffix: string) => `${tag} ${suffix}`;

/** Seeds numbered Drafts, D01 to Dnn, in this order. */
async function seedNumberedDrafts(setup: ApiSetup, commissioner: LoggedInUser, tag: string, count: number) {
  const drafts: Draft[] = [];

  for (let number = 1; number <= count; number++) {
    drafts.push(await seedDraft(setup, commissioner, tagged(tag, `D${String(number).padStart(2, '0')}`)));
  }
  return drafts;
}

async function seedDraft(
  setup: ApiSetup,
  commissioner: LoggedInUser,
  name: string,
  options: DraftOptions = {},
): Promise<Draft> {
  return setup.createDraft(commissioner, { name, rounds: 1, ...options });
}

/** Opens the route and shows only the Drafts of the test, which must exist. */
async function openHomeFor(page: Page, tag: string, route = '/home') {
  await page.goto(route);
  await nameSearch(page).fill(tag);
  // The table filters after a delay and then goes back to page 1, so wait for it before the next step
  await expect(draftRows(page).first()).toContainText(tag);
  await expect(draftRows(page).filter({ hasNotText: tag })).toHaveCount(0);
}

/**
 * Makes the Draft list hold only the Drafts of the test, from the real API answer. For the tests of the pager and of
 * the saved table state: the search box does not come back after a visit to a Draft, and a search is a second
 * change to the table that can undo a click on the pager.
 */
async function listOnlyDraftsTagged(page: Page, tag: string) {
  await page.route('**/api/drafts', async (route) => {
    const response = await route.fetch();
    const drafts: { draft_name: string }[] = await response.json();

    await route.fulfill({ response, json: drafts.filter((draft) => draft.draft_name.includes(tag)) });
  });
}

/** Opens the route with a search that no Draft matches. */
async function openHomeWithoutMatch(page: Page, label: string) {
  await page.goto('/home');
  await nameSearch(page).fill(unique(`${label} no such draft`));
  await expect(noDraftsHeading(page)).toBeVisible();
}

async function expectDraftNames(page: Page, names: string[]) {
  await expect(draftLinks(page)).toHaveText(names);
}

async function selectFilter(page: Page, filter: 'sport' | 'status', option: string) {
  await filterButton(page, filter).click();
  await filterDropdown(page, filter).getByRole('link', { name: option, exact: true }).click();
}

/** Selects a header three times and checks the order after each: ascending, descending, then the order of the API. */
async function expectSortCycle(page: Page, column: string, ascending: string[]) {
  const unsorted = await draftLinks(page).allTextContents();

  await header(page, column).click();
  await expectDraftNames(page, ascending);
  await header(page, column).click();
  await expectDraftNames(page, [...ascending].reverse());
  await header(page, column).click();
  await expectDraftNames(page, unsorted);
}

async function selectCommissioner(page: Page, name: string) {
  await commissionerSearch(page).fill(name);
  await page.getByRole('option', { name, exact: true }).click();
}

test('HOME-01 / and /home show the same screen with the Draft table', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home01');
  const locked = await seedDraft(setup, commissioner, tagged(tag, 'Locked'), {
    status: 'In Progress',
    password: 'secret-pw',
  });
  const open = await seedDraft(setup, commissioner, tagged(tag, 'Open'));
  const complete = await seedDraft(setup, commissioner, tagged(tag, 'Done'), { status: 'Complete' });

  for (const route of ['/', '/home']) {
    await openHomeFor(page, tag, route);

    await expect(page.getByRole('heading', { name: 'Welcome to Hoot Draft!' })).toBeVisible();
    for (const column of COLUMNS) {
      await expect(header(page, column)).toBeVisible();
    }
    await expect(draftRows(page)).toHaveCount(3);

    for (const draft of [locked, open, complete]) {
      const row = draftRows(page).filter({ hasText: draft.name });
      await expect(row.getByRole('link', { name: draft.name })).toHaveAttribute(
        'href',
        new RegExp(`draft/${draft.id}$`),
      );
      await expect(row).toContainText(commissioner.name);
    }
    await expect(lockIcon(page, locked)).toBeVisible();
    for (const unlocked of [open, complete]) {
      await expect(lockIcon(page, unlocked)).toBeHidden();
    }
  }
});

test('HOME-02 the search box keeps only the Drafts whose name contains the text', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-25');
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home02');
  const alpha = await seedDraft(setup, commissioner, tagged(tag, 'Alpha'));
  const beta = await seedDraft(setup, commissioner, tagged(tag, 'Beta'));

  await page.goto('/home');
  await nameSearch(page).fill(tagged(tag, 'Alp'));
  await expectDraftNames(page, [alpha.name]);

  await nameSearch(page).fill(tagged(tag, 'Beta'));
  await expectDraftNames(page, [beta.name]);

  // The Commissioner name is in a column of the table, but not in the Draft name
  await nameSearch(page).fill(commissioner.name);
  await expect(draftRows(page)).toHaveCount(0);
  await expect(page.getByText(NO_DRAFTS)).toBeVisible();
});

test('HOME-03 the Sport filter keeps the Drafts of one League until "remove"', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home03');
  const nfl = await seedDraft(setup, commissioner, tagged(tag, 'Football'), { league: 'NFL' });
  const mlb = await seedDraft(setup, commissioner, tagged(tag, 'Baseball'), { league: 'MLB' });

  await openHomeFor(page, tag);
  await expect(draftRows(page)).toHaveCount(2);

  await selectFilter(page, 'sport', 'MLB');
  await expectDraftNames(page, [mlb.name]);
  await expect(filterButton(page, 'sport')).toHaveText('MLB');

  await selectFilter(page, 'sport', 'remove');
  await expect(draftRows(page)).toHaveCount(2);
  await expect(filterButton(page, 'sport')).toHaveText('Filter');
  await expect(draftLinks(page).filter({ hasText: nfl.name })).toBeVisible();
});

test('HOME-04 the Status filter keeps the Drafts with one status until "remove"', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home04');
  const settingUp = await seedDraft(setup, commissioner, tagged(tag, 'Setting'));
  const inProgress = await seedDraft(setup, commissioner, tagged(tag, 'Progress'), { status: 'In Progress' });
  const complete = await seedDraft(setup, commissioner, tagged(tag, 'Done'), { status: 'Complete' });

  await openHomeFor(page, tag);
  await expect(draftRows(page)).toHaveCount(3);

  await selectFilter(page, 'status', 'In Progress');
  await expectDraftNames(page, [inProgress.name]);

  await selectFilter(page, 'status', 'remove');
  await expect(draftRows(page)).toHaveCount(3);

  await selectFilter(page, 'status', 'Setting Up');
  await expectDraftNames(page, [settingUp.name]);

  await selectFilter(page, 'status', 'remove');
  await selectFilter(page, 'status', 'Completed');
  await expectDraftNames(page, [complete.name]);
});

test('HOME-05 the Commissioner search gives at most 10 suggestions and filters by the one that is chosen', async ({
  page,
  setup,
}) => {
  const tag = unique('Home05');
  const commissioners = await Promise.all(
    Array.from({ length: 11 }, (_, index) =>
      setup.registerCommissioner({ name: tagged(tag, `Cm${String(index + 1).padStart(2, '0')}`) }),
    ),
  );
  const first = await seedDraft(setup, commissioners[0], tagged(tag, 'First'));
  const second = await seedDraft(setup, commissioners[1], tagged(tag, 'Second'));

  await openHomeFor(page, tag);
  await expect(draftRows(page)).toHaveCount(2);

  await commissionerSearch(page).fill(tagged(tag, 'Cm'));
  await expect(page.getByRole('option')).toHaveCount(10);

  await selectCommissioner(page, commissioners[1].name);
  await expect(commissionerSearch(page)).toBeHidden();
  await expect(page.getByText('Commish:').filter({ visible: true })).toBeVisible();
  await expect(commissionerLabel(page)).toContainText(commissioners[1].name);
  await expectDraftNames(page, [second.name]);

  await commissionerLabel(page).click();
  await expect(commissionerSearch(page)).toBeVisible();
  await expect(commissionerLabel(page)).toBeHidden();
  await expect(draftRows(page)).toHaveCount(2);
});

test('HOME-06 each header sorts ascending, then descending, then not at all', async ({ page, setup }) => {
  const tag = unique('Home06');
  // Each column has its own order of the three Drafts
  const [cm1, cm2, cm3] = await Promise.all(
    ['Cm1', 'Cm2', 'Cm3'].map((suffix) => setup.registerCommissioner({ name: tagged(tag, suffix) })),
  );
  const d1 = await seedDraft(setup, cm2, tagged(tag, 'D1'), { league: 'S15' });
  const d2 = await seedDraft(setup, cm3, tagged(tag, 'D2'), { status: 'Complete' });
  const d3 = await seedDraft(setup, cm1, tagged(tag, 'D3'), { league: 'NHL', status: 'In Progress' });

  await openHomeFor(page, tag);
  await expect(draftRows(page)).toHaveCount(3);

  await expectSortCycle(page, 'League Name', [d1.name, d2.name, d3.name]);
  await expectSortCycle(page, 'Sport', [d2.name, d3.name, d1.name]);
  await expectSortCycle(page, 'Commissioner', [d3.name, d1.name, d2.name]);
  // Setting Up, In Progress, Complete. The names of the statuses sort in a different order
  await expectSortCycle(page, 'Status', [d1.name, d3.name, d2.name]);
});

test('HOME-07 the Created header sorts, and the table starts with the newest Draft', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-25');
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home07');
  const oldest = await seedDraft(setup, commissioner, tagged(tag, 'Oldest'));
  const middle = await seedDraft(setup, commissioner, tagged(tag, 'Middle'));
  const newest = await seedDraft(setup, commissioner, tagged(tag, 'Newest'));
  await setDraftCreateTime(oldest.id, { hours: 72 });
  await setDraftCreateTime(middle.id, { hours: 48 });
  await setDraftCreateTime(newest.id, { hours: 24 });

  await openHomeFor(page, tag);
  await expectDraftNames(page, [newest.name, middle.name, oldest.name]);

  await header(page, 'Created').click();
  await expectDraftNames(page, [oldest.name, middle.name, newest.name]);
});

test('HOME-08 the table shows 15 rows on a page and a pager below it', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home08');
  const count = PAGE_SIZE + 1;
  const drafts = await seedNumberedDrafts(setup, commissioner, tag, count);

  await listOnlyDraftsTagged(page, tag);
  await page.goto('/home');
  await expect(draftRows(page)).toHaveCount(PAGE_SIZE);

  await expect(pager(page)).toBeVisible();
  const lastRowBox = await draftRows(page).last().boundingBox();
  const pagerBox = await pager(page).boundingBox();
  expect(pagerBox!.y).toBeGreaterThan(lastRowBox!.y + lastRowBox!.height - 1);
  await expect(pager(page).getByText('First')).toHaveCount(0);
  await expect(pager(page).getByText('Last')).toHaveCount(0);

  // Drafts that are created in the same second have no fixed order, so sort by name
  await header(page, 'League Name').click();
  await expect(draftLinks(page).first()).toHaveText(drafts[0].name);
  await pager(page).getByText('2', { exact: true }).click();
  await expectDraftNames(page, [drafts[PAGE_SIZE].name]);
});

test('HOME-09 an anonymous viewer sees the empty table without a link to create a Draft', async ({ page }) => {
  await openHomeWithoutMatch(page, 'Home09');

  await expect(draftRows(page)).toHaveCount(0);
  await expect(noDraftsHeading(page)).toBeVisible();
  await expect(resetButton(page)).toBeVisible();
  await expect(createDraftLink(page)).toHaveCount(0);
});

test('HOME-10 a Commissioner sees the link to create a Draft in the empty table', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  await setup.logInBrowser(context, commissioner);

  await openHomeWithoutMatch(page, 'Home10');

  await expect(noDraftsHeading(page)).toBeVisible();
  await expect(resetButton(page)).toBeVisible();
  await expect(createDraftLink(page)).toHaveAttribute('href', /commish\/draft\/create$/);
});

test('HOME-11 Reset All Filters shows each Draft again and clears the search box', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-25');
  const commissioner = await setup.registerCommissioner();
  await seedDraft(setup, commissioner, tagged(unique('Home11'), 'Draft'));

  await openHomeWithoutMatch(page, 'Home11');

  await resetButton(page).click();

  await expect(noDraftsHeading(page)).toBeHidden();
  await expect(draftRows(page).first()).toBeVisible();
  await expect(nameSearch(page)).toHaveValue('');
});

test('HOME-12 the filter, the sort, and the page stay after a visit to a Draft', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const tag = unique('Home12');
  const drafts = await seedNumberedDrafts(setup, commissioner, tag, PAGE_SIZE + 1);
  await seedDraft(setup, commissioner, tagged(tag, 'Other'), { league: 'MLB' });

  await listOnlyDraftsTagged(page, tag);
  await page.goto('/home');
  await selectFilter(page, 'sport', 'NFL');
  await header(page, 'League Name').click();
  await expect(draftLinks(page).first()).toHaveText(drafts[0].name);
  await header(page, 'League Name').click();
  await expect(draftLinks(page).first()).toHaveText(drafts[PAGE_SIZE].name);
  await pager(page).getByText('2', { exact: true }).click();
  // Descending: the first Draft is the only one on page 2
  await expectDraftNames(page, [drafts[0].name]);

  await draftLinks(page).first().click();
  await expect(page).toHaveURL(`/draft/${drafts[0].id}`);
  await page.goBack();

  await expect(filterButton(page, 'sport')).toHaveText('NFL');
  await expectDraftNames(page, [drafts[0].name]);
});

test('HOME-13 an API error on the Draft list gives a toast and the empty table', async ({ page }) => {
  await failApiRequest(page, { method: 'GET', path: 'drafts' });

  await page.goto('/home');

  await expectToast(page, { message: 'Unable to load drafts' });
  await expect(noDraftsHeading(page)).toBeVisible();
});

test('HOME-14 an API error on the Draft options gives a toast and filters without entries', async ({ page }) => {
  await failApiRequest(page, { method: 'GET', path: 'draftOptions' });

  await page.goto('/home');

  await expectToast(page, { title: 'Error', message: 'Cannot load draft options' });
  for (const filter of ['sport', 'status'] as const) {
    await filterButton(page, filter).click();
    await expect(filterDropdown(page, filter).getByRole('link')).toHaveCount(0);
  }
});

test('HOME-15 an API error on the Commissioner search gives a toast', async ({ page }) => {
  await failApiRequest(page, { method: 'GET', path: 'commissioners/search' });
  await page.goto('/home');

  await commissionerSearch(page).fill('abc');

  await expectToast(page, { message: 'Unable to search commissioners' });
});

test('HOME-16 a slow Draft list request shows a loading indicator', async ({ page }) => {
  knownFailureOnAngularJs('W-24');
  let release!: () => void;
  const gate = new Promise<void>((resolve) => (release = resolve));
  await page.route('**/api/drafts', async (route) => {
    await gate;
    await route.continue();
  });

  await page.goto('/home');

  try {
    await expect(page.getByText('Loading...')).toBeVisible();
  } finally {
    release();
  }
  await expect(page.getByText('Loading...')).toBeHidden();
});
