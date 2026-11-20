import { Browser, Page } from '@playwright/test';
import { ApiSetup, Draft, DraftOptions } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { openDraftScreen } from '../helpers/draft-screen';
import { expectMenuLinksOpenRoutes, logoLink } from '../helpers/navigation';
import { failApiRequest, recordApiRequests, waitForApiResponse } from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { expectToast } from '../helpers/toast';

// Route group 6A of docs/flow-inventory.md. Each test name starts with the row ID.

const POLL_MS = 2_750;
const FAILED_POLLS_BEFORE_PANEL = 5;
const LOAD_WARNING = 'We seem to be having trouble loading this draft, possibly due to network connectivity issues';
const PASSWORD = 'draft-secret';
const DEPTH_CHART = [{ position: 'QB', slots: 1 }];

const pageBody = (page: Page) => page.locator('#page_body');
const draftNav = (page: Page) => page.getByTestId('draft-navigation');
const navLink = (page: Page, name: string) => draftNav(page).getByRole('link', { name });
const toolboxButton = (page: Page) => draftNav(page).getByRole('button', { name: 'Commissioner Toolbox' });
const toolboxLinks = (page: Page) => page.getByTestId('commissioner-toolbox-menu').getByRole('link');
const picksButton = (page: Page) => draftNav(page).getByRole('button', { name: 'Picks' });
// The open dialog hides the panel from the accessibility tree, so the panel has a test ID
const lockedPanel = (page: Page) => page.getByTestId('draft-locked-panel');
const lockedHeading = (page: Page) => lockedPanel(page).getByText('Draft Locked');
const modalDialog = (page: Page) => page.getByRole('dialog');
const passwordField = (page: Page) => modalDialog(page).getByRole('textbox');
const passwordHeading = (page: Page) => page.getByRole('heading', { name: 'Draft is password protected' });
const enterPasswordButton = (page: Page) => modalDialog(page).getByRole('button', { name: 'Enter password' });
const connectionIssuesHeading = (page: Page) => page.getByRole('heading', { name: 'Connection Issues' });
const lastFivePicksHeading = (page: Page) => page.getByRole('heading', { name: 'Last 5 Picks' });
const boardLoading = (page: Page) => page.getByText('Loading the board, one sec.');
const draftRoute = (draft: Draft, path: string) => new RegExp(`/draft/${draft.id}/${path}$`);

const PICKS_MENU_ROUTES = [
  { link: 'by Manager', path: 'picks/manager' },
  { link: 'by Round', path: 'picks/round' },
  { link: 'Trades', path: 'trades' },
  { link: 'Search', path: 'search' },
];

async function expectPicksMenu(page: Page, draft: Draft) {
  await picksButton(page).click();
  for (const { link } of PICKS_MENU_ROUTES) {
    await expect(navLink(page, link)).toBeVisible();
  }

  await expectMenuLinksOpenRoutes(
    page,
    { linkByName: (name) => navLink(page, name), reopen: () => picksButton(page).click() },
    PICKS_MENU_ROUTES.map(({ link, path }) => ({ link, url: draftRoute(draft, path) })),
  );
}

async function expectNavLinks(page: Page, expected: { present: string[]; absent: string[] }) {
  for (const name of expected.present) {
    await expect(navLink(page, name)).toBeVisible();
  }
  for (const name of expected.absent) {
    await expect(navLink(page, name)).toHaveCount(0);
  }
}

async function expectToolbox(page: Page, entries: string[]) {
  await toolboxButton(page).click();
  await expect(toolboxLinks(page)).toHaveText(entries);
}

async function newViewerPage(browser: Browser) {
  const context = await browser.newContext();

  return { context, page: await context.newPage() };
}

async function expectDraftShows(page: Page) {
  await expect(lastFivePicksHeading(page)).toBeVisible();
}

async function submitPassword(page: Page, password: string) {
  await passwordField(page).fill(password);
  const reloaded = page.waitForEvent('load');
  await enterPasswordButton(page).click();
  await reloaded;
}

async function expectUnlocked(page: Page) {
  await expect(lockedHeading(page)).toBeHidden();
  await expect(modalDialog(page)).toHaveCount(0);
}

async function expectLockedScreen(page: Page) {
  await expect(lockedHeading(page)).toBeVisible();
  await expect(passwordHeading(page)).toBeVisible();
}

// The open dialog hides the page from the accessibility tree, so this asserts the visible text
async function expectNoDraftContent(page: Page, draft: Draft) {
  await expect(draftNav(page)).toHaveText(draft.name, { useInnerText: true });
  await expect(page.getByText('Last 5 Picks', { exact: true })).toBeHidden();
  await expect(boardLoading(page)).toBeHidden();
}

async function lockedDraft(setup: ApiSetup, options: DraftOptions = {}) {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { password: PASSWORD, status: 'In Progress', ...options });

  return { commissioner, draft };
}

async function gotoLockedDraft(page: Page, setup: ApiSetup) {
  const locked = await lockedDraft(setup);
  await page.goto(`/draft/${locked.draft.id}`);

  return locked;
}

test('DRAFT-01 a Setting Up Draft shows only the Draft name in the navigation bar', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'Setting Up' });

  await openDraftScreen(page, draft);

  await expect(draftNav(page).getByRole('link')).toHaveText([draft.name]);
  await expect(navLink(page, draft.name)).toHaveAttribute('href', new RegExp(`draft/${draft.id}$`));
  await expect(picksButton(page)).toHaveCount(0);
  await expect(toolboxButton(page)).toHaveCount(0);
});

test('DRAFT-02 an In Progress Draft with Depth Charts shows the links without Stats', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', depthChartPositions: DEPTH_CHART });

  await openDraftScreen(page, draft);

  await expectNavLinks(page, { present: ['Draft Board', 'Depth Charts'], absent: ['Stats'] });
  await expect(picksButton(page)).toBeVisible();

  await navLink(page, 'Draft Board').click();
  await expect(page).toHaveURL(draftRoute(draft, 'board'));
  await navLink(page, 'Depth Charts').click();
  await expect(page).toHaveURL(draftRoute(draft, 'depth_chart'));
  await expectPicksMenu(page, draft);
});

test('DRAFT-03 an In Progress Draft without Depth Charts shows no Depth Charts link', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await openDraftScreen(page, draft);

  await expectNavLinks(page, { present: ['Draft Board'], absent: ['Depth Charts', 'Stats'] });
  await expect(picksButton(page)).toBeVisible();
  await navLink(page, 'Draft Board').click();
  await expect(page).toHaveURL(draftRoute(draft, 'board'));
  await expectPicksMenu(page, draft);
});

test('DRAFT-04 a Complete Draft shows the links of DRAFT-02 and Stats', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, {
    status: 'Complete',
    rounds: 1,
    depthChartPositions: DEPTH_CHART,
  });

  await openDraftScreen(page, draft);

  await expectNavLinks(page, { present: ['Draft Board', 'Depth Charts', 'Stats'], absent: [] });
  await expect(picksButton(page)).toBeVisible();

  for (const { link, path } of [
    { link: 'Draft Board', path: 'board' },
    { link: 'Depth Charts', path: 'depth_chart' },
    { link: 'Stats', path: 'stats' },
  ]) {
    await navLink(page, link).click();
    await expect(page).toHaveURL(draftRoute(draft, path));
  }
  await expectPicksMenu(page, draft);
});

test('DRAFT-05 the toolbox of a Setting Up Draft shows four entries', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'Setting Up' });
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expectToolbox(page, ['Start Draft!', 'Setup Timers', 'Edit Draft', 'Delete Draft']);
});

test('DRAFT-06 the toolbox of an In Progress Draft shows six entries and its links open their routes', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expectToolbox(page, [
    'Enter Picks',
    'Edit a Pick',
    'Enter a Trade',
    'Setup Timers',
    'Reset Draft',
    'Delete Draft',
  ]);

  const toolboxLink = (name: string) => toolboxLinks(page).filter({ hasText: name });
  const confirmPrompts = [
    { entry: 'Reset Draft', title: 'Reset draft?' },
    { entry: 'Delete Draft', title: 'Delete the draft?' },
  ];
  for (const { entry, title } of confirmPrompts) {
    await toolboxLink(entry).click();
    await expect(modalDialog(page).getByRole('heading', { name: title, exact: true })).toBeVisible();
    await modalDialog(page).getByRole('button', { name: 'Cancel' }).click();
    await expect(modalDialog(page)).toHaveCount(0);
    await toolboxButton(page).click();
  }

  await expectMenuLinksOpenRoutes(
    page,
    {
      linkByName: toolboxLink,
      reopen: () => toolboxButton(page).click(),
    },
    [
      { link: 'Enter Picks', path: 'picks/add' },
      { link: 'Edit a Pick', path: 'picks/edit' },
      { link: 'Enter a Trade', path: 'trades/add' },
      { link: 'Setup Timers', path: 'timers' },
    ].map(({ link, path }) => ({ link, url: new RegExp(`/commish/draft/${draft.id}/${path}$`) })),
  );
});

test('DRAFT-07 the toolbox of a Complete Draft shows Delete Draft only', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'Complete', rounds: 1 });
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expectToolbox(page, ['Delete Draft']);
});

test('DRAFT-08 an Admin sees the toolbox of the Draft of a different user', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const admin = await setup.registerAdmin();
  const draft = await setup.createDraft(commissioner, { status: 'Setting Up' });
  await setup.logInBrowser(context, admin);

  await openDraftScreen(page, draft);

  await expectToolbox(page, ['Start Draft!', 'Setup Timers', 'Edit Draft', 'Delete Draft']);
});

test('DRAFT-09 another Commissioner gets the screen of an anonymous viewer', async ({
  page,
  context,
  browser,
  setup,
}) => {
  const owner = await setup.registerCommissioner();
  const other = await setup.registerCommissioner();
  const draft = await setup.createDraft(owner, { status: 'In Progress' });
  await setup.logInBrowser(context, other);

  await openDraftScreen(page, draft);
  const anonymous = await newViewerPage(browser);
  await openDraftScreen(anonymous.page, draft);

  // The name shows before the list of upcoming Picks, so wait for the list before the text is read
  for (const viewer of [page, anonymous.page]) {
    await expect(viewer.getByText('On the clock').filter({ visible: true })).toHaveCount(1);
    await expect(toolboxButton(viewer)).toHaveCount(0);
  }
  await expect(pageBody(page)).toHaveText(await pageBody(anonymous.page).innerText(), { useInnerText: true });
  await anonymous.context.close();
});

test('DRAFT-10 the logo hides the Draft navigation bar on /home', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await openDraftScreen(page, draft);
  await expect(draftNav(page)).toBeVisible();

  await logoLink(page).click();

  await expect(page).toHaveURL(/\/home$/);
  await expect(draftNav(page)).toBeHidden();
});

test('DRAFT-11 a missing Draft shows the panel "Unable to load draft" and one toast', async ({ page }) => {
  knownFailureOnAngularJs('W-07');

  await page.goto('/draft/2147483647');

  await expectToast(page, { message: LOAD_WARNING });
  await expect(page.locator('#toast-container .toast')).toHaveCount(1);
  await expect(page.getByRole('heading', { name: 'Unable to load draft' })).toBeVisible();
});

/** Moves the paused clock in steps until the assertion passes. Locator assertions inside it need `timeout: 0`. */
async function runClockUntil(page: Page, stepMs: number, assertion: () => Promise<void>) {
  await expect(async () => {
    await page.clock.runFor(stepMs);
    await assertion();
  }).toPass({ intervals: [0] });
}

/**
 * Runs one poll that fails, and waits until the client has swapped the old warning toast for a new one. The client
 * polls on a fixed interval whose phase depends on real time, so the clock moves until the request is sent rather
 * than by one interval. The old toast is held by its element because the new toast has the same text. The last
 * check shows that the toast steps did not reach the next poll.
 */
async function runFailedPoll(page: Page, draft: Draft, requests: string[], expectedRequests: number) {
  const warnings = page.getByText(LOAD_WARNING, { exact: true });
  const oldWarning = (await warnings.count()) > 0 ? await warnings.elementHandle() : null;
  const failedPoll = waitForApiResponse(page, { path: `draft/${draft.id}` });

  await runClockUntil(page, 500, async () => expect(requests).toHaveLength(expectedRequests));
  await failedPoll;

  await runClockUntil(page, 100, async () => {
    expect(await oldWarning?.evaluate((element) => element.isConnected)).toBeFalsy();
    await expect(warnings).toHaveCount(1, { timeout: 0 });
  });
  expect(requests).toHaveLength(expectedRequests);
}

test('DRAFT-12 failed polls show the warning toast, and five failures show the panel "Connection Issues"', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await page.clock.install();
  await openDraftScreen(page, draft);
  await expect(lastFivePicksHeading(page)).toBeVisible();
  // Until now the clock ran in real time, and a real poll would count as a failure
  await page.clock.pauseAt(new Date(Date.now() + 1_000));

  await failApiRequest(page, { path: `draft/${draft.id}` });
  const requests = recordApiRequests(page, { path: `draft/${draft.id}` });

  for (let failure = 1; failure < FAILED_POLLS_BEFORE_PANEL; failure++) {
    await runFailedPoll(page, draft, requests, failure);
    await expect(page.getByText(LOAD_WARNING, { exact: true })).toHaveCount(1);
    await expect(connectionIssuesHeading(page)).toBeHidden();
  }

  await runFailedPoll(page, draft, requests, FAILED_POLLS_BEFORE_PANEL);

  await expect(connectionIssuesHeading(page)).toBeVisible();
  await expect(lastFivePicksHeading(page)).toBeHidden();
  const failedPolls = requests.length;
  await page.clock.runFor(POLL_MS * 3);
  expect(requests).toHaveLength(failedPolls);
});

test('DRAFT-13 the client polls the In Progress Draft only, and stops after the viewer leaves the Draft routes', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const settingUp = await setup.createDraft(commissioner, { status: 'Setting Up' });
  const inProgress = await setup.createDraft(commissioner, { status: 'In Progress' });
  const complete = await setup.createDraft(commissioner, { status: 'Complete', rounds: 1 });
  await page.clock.install();

  for (const draft of [settingUp, complete]) {
    await openDraftScreen(page, draft);
    const requests = recordApiRequests(page, { path: `draft/${draft.id}` });

    await page.clock.runFor(POLL_MS * 3);
    expect(requests).toEqual([]);
  }

  await openDraftScreen(page, inProgress);
  const polls = recordApiRequests(page, { path: `draft/${inProgress.id}` });

  // One poll at a time: WebKit sends one request for identical GET requests that are in flight together
  for (let poll = 1; poll <= 3; poll++) {
    const answered = waitForApiResponse(page, { path: `draft/${inProgress.id}` });
    await page.clock.runFor(POLL_MS);
    await answered;
  }
  expect(polls.length).toBeGreaterThanOrEqual(3);

  await logoLink(page).click();
  await expect(page).toHaveURL(/\/home$/);
  const pollsAfterLeaving = polls.length;

  await page.clock.runFor(POLL_MS * 3);
  expect(polls).toHaveLength(pollsAfterLeaving);
});

test('PW-01 a locked Draft shows the panel and the dialog and no Draft content', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-08');
  const { commissioner, draft } = await gotoLockedDraft(page, setup);

  await expectLockedScreen(page);
  await expect(passwordField(page)).toBeVisible();
  await expectNoDraftContent(page, draft);
  await expect(lockedPanel(page)).toContainText(`${commissioner.name} has locked this draft`);
  await expect(lockedPanel(page)).toContainText(`If your name happens to be ${commissioner.name}`);
});

test('PW-02 the right Draft Password opens the Draft and the other routes with no dialog', async ({ page, setup }) => {
  const { draft } = await gotoLockedDraft(page, setup);

  await submitPassword(page, PASSWORD);

  await expectDraftShows(page);
  await expect(navLink(page, draft.name)).toBeVisible();
  await expectUnlocked(page);

  await navLink(page, 'Draft Board').click();
  await expect(page).toHaveURL(draftRoute(draft, 'board'));
  await page.goto(`/draft/${draft.id}/trades`);
  await expect(navLink(page, draft.name)).toBeVisible();
  await expectUnlocked(page);
});

test('PW-03 a wrong Draft Password shows the panel and the dialog again with the wrong value', async ({
  page,
  setup,
}) => {
  const { draft } = await gotoLockedDraft(page, setup);

  await submitPassword(page, 'not-the-password');

  await expectLockedScreen(page);
  await expect(passwordField(page)).toHaveValue('not-the-password');
});

test('PW-04 Cancel closes the dialog, and the panel link opens it again', async ({ page, setup }) => {
  const { draft } = await gotoLockedDraft(page, setup);

  await modalDialog(page).getByRole('button', { name: 'Cancel' }).click();

  await expect(modalDialog(page)).toHaveCount(0);
  await expect(lockedHeading(page)).toBeVisible();

  await lockedPanel(page).getByRole('link', { name: 'provide a password' }).click();

  await expect(passwordHeading(page)).toBeVisible();
});

test('PW-05 the login link of the dialog closes it and opens /login', async ({ page, setup }) => {
  const { draft } = await gotoLockedDraft(page, setup);

  await modalDialog(page).getByRole('link', { name: 'login to HootDraft' }).click();

  await expect(page).toHaveURL(/\/login$/);
  await expect(modalDialog(page)).toHaveCount(0);
});

test('PW-06 another Commissioner gets the dialog with no login link', async ({ page, context, setup }) => {
  const { draft } = await lockedDraft(setup);
  const other = await setup.registerCommissioner();
  await setup.logInBrowser(context, other);

  await page.goto(`/draft/${draft.id}`);

  await expectLockedScreen(page);
  await expect(passwordField(page)).toBeVisible();
  await expectNoDraftContent(page, draft);
  await expect(modalDialog(page).getByRole('link', { name: 'login to HootDraft' })).toHaveCount(0);
});

test('PW-07 the Commissioner sees the locked Draft with no panel and no dialog', async ({ page, context, setup }) => {
  const { commissioner, draft } = await lockedDraft(setup);
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expectUnlocked(page);
  await expectDraftShows(page);
});

test('PW-08 an Admin sees the locked Draft of a different user with no panel and no dialog', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await lockedDraft(setup);
  const admin = await setup.registerAdmin();
  await setup.logInBrowser(context, admin);

  await openDraftScreen(page, draft);

  await expectUnlocked(page);
  await expectDraftShows(page);
});

test('PW-09 each public route of a locked Draft shows the panel and the dialog only', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-09');
  const { draft } = await lockedDraft(setup, { status: 'Complete', rounds: 1, depthChartPositions: DEPTH_CHART });

  for (const path of ['board', 'picks/manager', 'picks/round', 'search', 'trades', 'stats', 'depth_chart']) {
    await page.goto(`/draft/${draft.id}/${path}`);

    await expectLockedScreen(page);
    await expectNoDraftContent(page, draft);
    await expect(page.getByText('Unable to grab managers')).toHaveCount(0);
    await expect(page.getByText('Unable to load managers')).toHaveCount(0);
  }
});

test('PW-10 the close button of the dialog closes it like Cancel', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-10');
  const { draft } = await gotoLockedDraft(page, setup);

  await modalDialog(page).getByRole('button', { name: 'Close' }).click();

  await expect(modalDialog(page)).toHaveCount(0);
  await expect(lockedHeading(page)).toBeVisible();
});

test('PW-11 a second viewer sees a new Pick on the Draft Board after the next poll', async ({ browser, setup }) => {
  const { commissioner, draft } = await lockedDraft(setup);
  const viewer = await newViewerPage(browser);
  await viewer.page.clock.install();
  await viewer.page.goto(`/draft/${draft.id}/board`);
  await submitPassword(viewer.page, PASSWORD);
  await expect(boardLoading(viewer.page)).toBeHidden();

  const pick = await setup.addPick(commissioner, draft.id, { firstName: 'Davante', lastName: 'Adams' });
  await viewer.page.clock.runFor(POLL_MS * 2);

  await expect(viewer.page.getByText(`${pick.firstName} ${pick.lastName}`).first()).toBeVisible();
  await viewer.context.close();
});

test('PW-12 the client does not poll while the panel shows', async ({ page, setup }) => {
  const { draft } = await lockedDraft(setup);
  await page.clock.install();
  await page.goto(`/draft/${draft.id}`);
  await expectLockedScreen(page);
  const requests = recordApiRequests(page, { path: `draft/${draft.id}` });

  await page.clock.runFor(POLL_MS * 4);

  expect(requests).toEqual([]);
});
