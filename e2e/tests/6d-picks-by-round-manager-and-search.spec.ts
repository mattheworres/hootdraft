import { Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser, SelectedPick, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { DRAFT_POLL_MS, openDraftScreen, openRoute, openWithClock, visiblePanel } from '../helpers/draft-screen';
import { failApiRequest } from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { VISIBLE_TEXT } from '../helpers/text';
import { expectToast } from '../helpers/toast';

// Route group 6D of docs/flow-inventory.md. Each test name starts with the row ID.

const SETTING_UP_TOAST = 'Draft is still setting up';
const SEARCH_HINT =
  'Enter a few player name keywords, select a team or a position to search for picks in the search box to find picks.';
const NO_MATCHES = 'No picks were found matching that criteria.';
const SEARCH_DEBOUNCE_MS = 500;

const roundRoute = (draft: Pick<Draft, 'id'>) => `/draft/${draft.id}/picks/round`;
const managerRoute = (draft: Pick<Draft, 'id'>) => `/draft/${draft.id}/picks/manager`;
const searchRoute = (draft: Pick<Draft, 'id'>) => `/draft/${draft.id}/search`;

const pickRows = (panel: Locator) => panel.getByTestId('pick-row');
const roundPanel = (page: Page) => page.getByTestId('round-picks-panel');
const managerPanel = (page: Page) => page.getByTestId('manager-picks-panel');
const managerSelect = (page: Page) => page.getByTestId('manager-select');
const upcomingPanel = (page: Page) => page.getByTestId('upcoming-picks-panel');
const leagueDetailsPanel = (page: Page) => visiblePanel(page, 'league-details-panel');
const resultsPanel = (page: Page) => page.getByTestId('search-results-panel');
const keywords = (page: Page) => page.getByTestId('search-keywords');
const positionSelect = (page: Page) => page.getByTestId('search-position');
const teamSelect = (page: Page) => page.getByTestId('search-team');
const sortButton = (page: Page) => page.getByRole('button', { name: /picks first/ });
const clearButton = (page: Page) => page.getByRole('button', { name: 'Clear' });
const roundHeading = (page: Page, round: number) => page.getByRole('heading', { name: `Round ${round} Picks` });
const managerHeading = (page: Page, name: string) => page.getByRole('heading', { name: `${name}'s Picks` });
const pager = (page: Page, direction: 'Previous' | 'Next') =>
  roundPanel(page).getByRole('link', { name: new RegExp(direction) });

const fullName = (pick: Pick<SelectedPick, 'firstName' | 'lastName'>) => `${pick.firstName} ${pick.lastName}`;
const managerName = (draft: Pick<Draft, 'managers'>, pick: Pick<SelectedPick, 'managerId'>) =>
  draft.managers.find(({ id }) => id === pick.managerId)!.name;

/** The text of a Pick row: its number and Round, then each of the `parts` in order. */
function row(number: number, round: number, ...parts: string[]): RegExp {
  return new RegExp([`Pick #\\s*${number}\\b`, `Round\\s*${round}\\b`, ...parts].join('[\\s\\S]*'));
}

const selectedRow = (draft: Pick<Draft, 'managers'>, pick: SelectedPick) =>
  row(pick.number, pick.round, fullName(pick), managerName(draft, pick));

/** An In Progress Draft of two Managers who pick in turn, so each Manager has every second Pick. */
function standardDraft(setup: ApiSetup, commissioner: LoggedInUser, rounds = 2): Promise<Draft> {
  return setup.createDraft(commissioner, { status: 'In Progress', style: 'standard', rounds });
}

/** A Complete Draft of one Round and two Managers. Returns its Selected Picks in Pick order. */
async function completeDraft(setup: ApiSetup, commissioner: LoggedInUser) {
  const draft = await standardDraft(setup, commissioner, 1);
  const picks = await setup.addPicks(commissioner, draft, 2);

  return { draft, picks };
}

async function expectSettingUpRedirect(page: Page, route: (draft: Pick<Draft, 'id'>) => string, setup: ApiSetup) {
  const commissioner = await setup.registerCommissioner();
  const settingUp = await setup.createDraft(commissioner);
  const other = await setup.createDraft(commissioner, { status: 'In Progress' });

  const previousRoute = await openDraftScreen(page, other);
  await page.goto(route(settingUp));

  await expectToast(page, { message: SETTING_UP_TOAST });
  await expect(page).toHaveURL(previousRoute);
}

async function expectUpcomingPicksPanel(page: Page) {
  await expect(upcomingPanel(page).getByRole('heading', { name: 'Upcoming Picks' })).toBeVisible();
}

/** The Complete view of the Picks lists: "League Details" takes the place of "Upcoming Picks". */
async function expectLeagueDetailsInPlaceOfUpcoming(page: Page, draft: Draft) {
  await expect(leagueDetailsPanel(page).getByRole('heading', { name: `League Details: ${draft.name}` })).toBeVisible();
  await expect(upcomingPanel(page)).toBeHidden();
}

async function expectManagerList(page: Page, draft: Pick<Draft, 'managers'>, chosen: string) {
  await expect(managerSelect(page).locator('option')).toHaveText(draft.managers.map(({ name }) => name));
  await expect(managerSelect(page).locator('option:checked')).toHaveText(chosen);
}

// Picks by Round

test('ROUND-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectSettingUpRedirect(page, roundRoute, setup);
});

test('ROUND-02 the current Round lists each Pick, Selected and Unselected, next to Upcoming Picks', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner, 3);
  const [, second] = draft.managers;
  const picks = await setup.addPicks(commissioner, draft, 3);

  await page.goto(roundRoute(draft));

  await expect(roundHeading(page, 2)).toBeVisible();
  await expect(pickRows(roundPanel(page))).toHaveText(
    [selectedRow(draft, picks[2]), row(4, 2, second.name, 'On the clock')],
    VISIBLE_TEXT,
  );
  await expect(pager(page, 'Previous')).toBeVisible();
  await expect(pager(page, 'Next')).toBeVisible();
  await expectUpcomingPicksPanel(page);
  await expect(pickRows(upcomingPanel(page))).toHaveCount(3);
});

test('ROUND-03 the pager moves between the Rounds and stops at the first and last Round', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner, 3);
  const picks = await setup.addPicks(commissioner, draft, 3);
  await page.goto(roundRoute(draft));
  await expect(roundHeading(page, 2)).toBeVisible();

  await pager(page, 'Previous').click();

  await expect(roundHeading(page, 1)).toBeVisible();
  await expect(pickRows(roundPanel(page))).toHaveText(
    [selectedRow(draft, picks[0]), selectedRow(draft, picks[1])],
    VISIBLE_TEXT,
  );

  // The pager CSS (AngularJS: drafts.less) hides its first and last link
  await expect(pager(page, 'Previous')).toHaveCount(0);
  await expect(roundHeading(page, 1)).toBeVisible();

  await pager(page, 'Next').click();
  await pager(page, 'Next').click();

  await expect(roundHeading(page, 3)).toBeVisible();
  await expect(pickRows(roundPanel(page))).toHaveText(
    [row(5, 3, draft.managers[0].name), row(6, 3, draft.managers[1].name)],
    VISIBLE_TEXT,
  );

  await expect(pager(page, 'Next')).toHaveCount(0);
  await expect(roundHeading(page, 3)).toBeVisible();
});

test('ROUND-04 a Complete Draft shows Round 1 next to League Details', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDraft(setup, commissioner);

  await page.goto(roundRoute(draft));

  await expect(roundHeading(page, 1)).toBeVisible();
  await expect(pickRows(roundPanel(page))).toHaveText(
    picks.map((pick) => selectedRow(draft, pick)),
    VISIBLE_TEXT,
  );
  await expectLeagueDetailsInPlaceOfUpcoming(page, draft);
});

test('ROUND-05 an API error gives a toast and the error text in the panel', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-24, W-43');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  await failApiRequest(page, { path: /^draft\/\d+\/round\/\d+\/picks\/all$/ });

  await page.goto(roundRoute(draft));

  await expectToast(page, { message: 'Unable to load picks' });
  await expect(roundPanel(page)).toContainText('Unable to load round 1 picks', VISIBLE_TEXT);
});

test('ROUND-06 a second viewer sees a new Pick after the next poll', async ({ newViewerPage, setup }) => {
  knownFailureOnAngularJs('W-12');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const viewer = await newViewerPage();
  await openWithClock(viewer, roundRoute(draft), roundHeading(viewer, 1));
  await expect(pickRows(roundPanel(viewer)).first()).toContainText('On the clock', VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(pickRows(roundPanel(viewer)).first()).toHaveText(selectedRow(draft, pick), VISIBLE_TEXT);
  await expect(pickRows(upcomingPanel(viewer)).first()).toHaveText(row(2, 1), VISIBLE_TEXT);
});

// Picks by Manager

test('MANAGER-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectSettingUpRedirect(page, managerRoute, setup);
});

test('MANAGER-02 the first Manager is chosen and lists each of their Picks next to Upcoming Picks', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-42');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const [first] = draft.managers;
  const [firstPick] = await setup.addPicks(commissioner, draft, 2);

  await page.goto(managerRoute(draft));

  await expect(managerHeading(page, first.name)).toBeVisible();
  await expectManagerList(page, draft, first.name);
  await expect(pickRows(managerPanel(page))).toHaveText(
    [selectedRow(draft, firstPick), row(3, 2, first.name, 'On the clock')],
    VISIBLE_TEXT,
  );
  await expectUpcomingPicksPanel(page);
});

test('MANAGER-03 choosing a different Manager changes the heading and the Picks', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-42');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const [, second] = draft.managers;
  const [, secondPick] = await setup.addPicks(commissioner, draft, 2);
  await page.goto(managerRoute(draft));
  await expect(pickRows(managerPanel(page))).toHaveCount(2);

  await managerSelect(page).selectOption({ label: second.name });

  await expect(managerHeading(page, second.name)).toBeVisible();
  await expect(pickRows(managerPanel(page))).toHaveText(
    [selectedRow(draft, secondPick), row(4, 2, second.name)],
    VISIBLE_TEXT,
  );
});

test('MANAGER-04 a Complete Draft shows the first Manager next to League Details', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-42');
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDraft(setup, commissioner);
  const [first] = draft.managers;

  await page.goto(managerRoute(draft));

  await expect(managerHeading(page, first.name)).toBeVisible();
  await expectManagerList(page, draft, first.name);
  await expect(pickRows(managerPanel(page))).toHaveText([selectedRow(draft, picks[0])], VISIBLE_TEXT);
  await expectLeagueDetailsInPlaceOfUpcoming(page, draft);
});

test('MANAGER-05 an API error gives a toast', async ({ context, page, setup }) => {
  knownFailureOnAngularJs('W-43');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  await failApiRequest(page, { path: /^draft\/\d+\/managers$/ });

  await page.goto(managerRoute(draft));

  await expectToast(page, { message: 'Unable to grab managers' });

  const picksPage = await context.newPage();
  await failApiRequest(picksPage, { path: /^draft\/\d+\/manager\/\d+\/picks\/all$/ });

  await picksPage.goto(managerRoute(draft));

  await expectToast(picksPage, { message: 'Unable to load picks' });
});

test('MANAGER-06 a second viewer sees the Pick of the Manager after the next poll', async ({
  newViewerPage,
  setup,
}) => {
  knownFailureOnAngularJs('W-12');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const [first] = draft.managers;
  const viewer = await newViewerPage();
  await openWithClock(viewer, managerRoute(draft), managerHeading(viewer, first.name));
  await expect(pickRows(managerPanel(viewer)).first()).toContainText('On the clock', VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(pickRows(managerPanel(viewer)).first()).toHaveText(selectedRow(draft, pick), VISIBLE_TEXT);
  await expect(pickRows(upcomingPanel(viewer)).first()).toHaveText(row(2, 1), VISIBLE_TEXT);
});

// Search

/** An In Progress Draft with these Picks entered in order. */
async function draftWithPicks(
  setup: ApiSetup,
  commissioner: LoggedInUser,
  entries: Parameters<ApiSetup['addPick']>[2][],
): Promise<{ draft: Draft; picks: SelectedPick[] }> {
  const draft = await standardDraft(setup, commissioner, 3);
  const picks: SelectedPick[] = [];

  for (const entry of entries) {
    picks.push(await setup.addPick(commissioner, draft, entry));
  }

  return { draft, picks };
}

async function expectResults(page: Page, draft: Pick<Draft, 'managers'>, picks: SelectedPick[]) {
  await expect(pickRows(resultsPanel(page))).toHaveText(
    picks.map((pick) => selectedRow(draft, pick)),
    VISIBLE_TEXT,
  );
}

async function expectEmptyFieldsAndHint(page: Page) {
  await expect(keywords(page)).toHaveValue('');
  await expect(positionSelect(page).locator('option:checked')).toHaveText('(all positions)');
  await expect(teamSelect(page).locator('option:checked')).toHaveText('(all teams)');
  await expect(positionSelect(page).locator('option', { hasText: '(all positions)' })).toHaveCount(1);
  await expect(teamSelect(page).locator('option', { hasText: '(all teams)' })).toHaveCount(1);
  await expect(resultsPanel(page)).toContainText(SEARCH_HINT, VISIBLE_TEXT);
}

test('SEARCH-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectSettingUpRedirect(page, searchRoute, setup);
});

test('SEARCH-02 the search form shows its fields and the hint, for an In Progress and a Complete Draft', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-41');
  const commissioner = await setup.registerCommissioner();
  const { draft: inProgress } = await draftWithPicks(setup, commissioner, [{}]);
  const { draft: complete } = await completeDraft(setup, commissioner);

  for (const draft of [inProgress, complete]) {
    await page.goto(searchRoute(draft));

    await expect(page.getByRole('heading', { name: 'Search Selected Picks' })).toBeVisible();
    await expect(page.getByText('Keywords', { exact: true })).toBeVisible();
    await expect(page.getByText('Position', { exact: true })).toBeVisible();
    await expect(page.getByText('Team', { exact: true })).toBeVisible();
    await expect(keywords(page)).toBeVisible();
    await expectEmptyFieldsAndHint(page);
    await expect(sortButton(page)).toHaveText(/Most recent picks first/);
    await expect(clearButton(page)).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Matching Picks' })).toBeVisible();
    await expect(pickRows(resultsPanel(page))).toHaveCount(0);
  }
});

test('SEARCH-03 keywords list the Selected Picks that match, after half a second', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await draftWithPicks(setup, commissioner, [
    { firstName: 'Davante', lastName: unique('Adams') },
    { firstName: 'Justin', lastName: unique('Jefferson') },
  ]);
  await page.clock.install();
  await openRoute(page, searchRoute(draft), keywords(page));
  await page.clock.pauseAt(new Date(Date.now() + 1_000));

  await keywords(page).fill('Dava');
  await page.clock.runFor(SEARCH_DEBOUNCE_MS - 1);

  await expect(resultsPanel(page)).toContainText(SEARCH_HINT, VISIBLE_TEXT);
  await expect(pickRows(resultsPanel(page))).toHaveCount(0);

  await page.clock.runFor(1);
  // The hint hides in a later timer, so let the clock run again
  await page.clock.resume();

  await expectResults(page, draft, [picks[0]]);
  await expect(resultsPanel(page)).not.toContainText(SEARCH_HINT, VISIBLE_TEXT);
});

test('SEARCH-04 a position lists only the Selected Picks with that position', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await draftWithPicks(setup, commissioner, [
    { position: 'QB' },
    { position: 'RB' },
    { position: 'RB' },
  ]);
  await page.goto(searchRoute(draft));

  await positionSelect(page).selectOption({ label: 'Runningback' });

  await expectResults(page, draft, [picks[2], picks[1]]);
});

test('SEARCH-05 a team lists only the Selected Picks with that team', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await draftWithPicks(setup, commissioner, [
    { team: 'GNB' },
    { team: 'CHI' },
    { team: 'GNB' },
  ]);
  await page.goto(searchRoute(draft));

  await teamSelect(page).selectOption({ label: 'Chicago Bears' });

  await expectResults(page, draft, [picks[1]]);
});

test('SEARCH-06 the sort button reverses the order of the results, and a second click reverses it back', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await draftWithPicks(setup, commissioner, [{}, {}, {}]);
  await page.goto(searchRoute(draft));
  await positionSelect(page).selectOption({ label: 'Wide Receiver' });
  await expectResults(page, draft, [picks[2], picks[1], picks[0]]);

  await sortButton(page).click();

  await expect(sortButton(page)).toHaveText(/Highest picks first/);
  await expectResults(page, draft, picks);

  await sortButton(page).click();

  await expect(sortButton(page)).toHaveText(/Most recent picks first/);
  await expectResults(page, draft, [picks[2], picks[1], picks[0]]);
});

test('SEARCH-07 keywords that match nothing show the no-match text', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft } = await draftWithPicks(setup, commissioner, [{}]);
  await page.goto(searchRoute(draft));

  await keywords(page).fill('Zzzzzzzz');

  await expect(resultsPanel(page)).toContainText(NO_MATCHES, VISIBLE_TEXT);
  await expect(pickRows(resultsPanel(page))).toHaveCount(0);
});

test('SEARCH-08 Clear empties the fields, shows the hint, and removes the results', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-21, W-41');
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await draftWithPicks(setup, commissioner, [{}, {}]);
  await page.goto(searchRoute(draft));
  await keywords(page).fill('Pick');
  await expectResults(page, draft, [picks[1], picks[0]]);

  await clearButton(page).click();

  await expectEmptyFieldsAndHint(page);
  await expect(pickRows(resultsPanel(page))).toHaveCount(0);
});

test('SEARCH-09 an API error gives a toast and the error text', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft } = await draftWithPicks(setup, commissioner, [{}]);
  await failApiRequest(page, { path: /^draft\/\d+\/picks\/search$/ });
  await page.goto(searchRoute(draft));

  await keywords(page).fill('Pick');

  await expectToast(page, { message: 'Unable to search picks' });
  await expect(resultsPanel(page)).toContainText('Unable to load search picks', VISIBLE_TEXT);
});

test('SEARCH-10 a second viewer sees a new matching Pick after the next poll', async ({ newViewerPage, setup }) => {
  knownFailureOnAngularJs('W-12');
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const viewer = await newViewerPage();
  await openWithClock(viewer, searchRoute(draft), keywords(viewer));
  await keywords(viewer).fill('Dava');
  await expect(resultsPanel(viewer)).toContainText(NO_MATCHES, VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft, { firstName: 'Davante', lastName: unique('Adams') });
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expectResults(viewer, draft, [pick]);
});
