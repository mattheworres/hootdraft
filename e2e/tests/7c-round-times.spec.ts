import { BrowserContext, Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { boardRoute, expectFlipClockBetween, pickTimer, pickTimerDisabled } from '../helpers/draft-board';
import {
  commishActions,
  DraftRoute,
  expectRedirectToPreviousRoute,
  openDraftScreen,
  openWithClock,
} from '../helpers/draft-screen';
import { failApiRequest, recordApiRequests, waitForApiResponse } from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { VISIBLE_TEXT } from '../helpers/text';
import { expectToast } from '../helpers/toast';

// Route group 7C of docs/flow-inventory.md. Each test name starts with the row ID.

const timersPath = (draft: Pick<Draft, 'id'>) => `commish/draft/${draft.id}/timers`;
const timersRoute: DraftRoute = (draft) => `/${timersPath(draft)}`;
const indexRoute: DraftRoute = (draft) => `/draft/${draft.id}`;

const heading = (page: Page) => page.getByRole('heading', { name: 'Pick Timers' });
const timersSwitch = (page: Page) => page.getByTestId('round-times-switch');
const sameRoundTimeSwitch = (page: Page) => page.getByTestId('same-round-time-switch');
const maxDraftLength = (page: Page) => page.getByTestId('max-draft-length');
const roundTimeRows = (page: Page) => page.getByTestId('round-time-row').filter({ visible: true });
const minutesField = (page: Page, row: number) => roundTimeRows(page).nth(row).getByTestId('round-time-minutes');
const secondsField = (page: Page, row: number) => roundTimeRows(page).nth(row).getByTestId('round-time-seconds');
const saveButton = (page: Page) => page.getByRole('button', { name: /Save Timers/ });
const cancelButton = (page: Page) => page.getByRole('button', { name: 'Cancel' });
const loadingText = (page: Page) => page.getByText('Loading your timers...');

const SAVED_TOAST = 'Round timers saved!';

interface RoundTime {
  minutes: number;
  seconds: number;
}

/** The toggle switch library marks the state of the switch with a class. */
async function expectSwitch(toggle: Locator, state: 'on' | 'off') {
  await expect(toggle.locator(state === 'on' ? '.switch-on' : '.switch-off')).toHaveCount(1);
}

/**
 * The form is ready when the heading shows and the timers and Managers have arrived. The form shows before then,
 * and the answer of the timers request resets the switch, so a click before it gets lost.
 */
async function openTimers(page: Page, draft: Pick<Draft, 'id'>) {
  const timers = waitForApiResponse(page, { method: 'GET', path: timersPath(draft) });
  const managers = waitForApiResponse(page, { method: 'GET', path: `draft/${draft.id}/managers` });

  await page.goto(timersRoute(draft));
  await Promise.all([timers, managers]);
  await expectTimersLoaded(page);
}

async function expectTimersLoaded(page: Page) {
  await expect(heading(page)).toBeVisible();
  await expect(timersSwitch(page)).toBeVisible();
  await expect(loadingText(page)).toBeHidden();
}

/** Opens the timers from the Draft index, so that Cancel and the previous route have an entry to go back to. */
async function openTimersFromIndex(page: Page, draft: Draft) {
  await openDraftScreen(page, draft);
  const timers = waitForApiResponse(page, { method: 'GET', path: timersPath(draft) });
  await commishActions(page).getByRole('link', { name: 'Timers' }).click();
  await expect(page).toHaveURL(timersRoute(draft));
  await timers;
  await expectTimersLoaded(page);
}

/** Logs in a new Commissioner with a Setting Up Draft of 4 Rounds and opens its timers. */
async function openNewDraftTimers(
  page: Page,
  context: BrowserContext,
  setup: ApiSetup,
  open: (page: Page, draft: Draft) => Promise<void> = openTimers,
): Promise<{ commissioner: LoggedInUser; draft: Draft }> {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { rounds: 4 });
  await open(page, draft);

  return { commissioner, draft };
}

async function setTime(page: Page, row: number, time: RoundTime) {
  await minutesField(page, row).fill(String(time.minutes));
  await secondsField(page, row).fill(String(time.seconds));
}

async function expectTime(page: Page, row: number, time: RoundTime) {
  await expect(minutesField(page, row)).toHaveValue(String(time.minutes));
  await expect(secondsField(page, row)).toHaveValue(String(time.seconds));
}

async function expectAllRow(page: Page) {
  await expect(roundTimeRows(page)).toHaveCount(1);
  await expect(roundTimeRows(page).first()).toContainText('(all)');
}

async function turnOnTimers(page: Page) {
  await timersSwitch(page).click();
  await expect(maxDraftLength(page)).toBeVisible();
}

async function saveTimers(page: Page) {
  await saveButton(page).click();
  await expectToast(page, { message: SAVED_TOAST });
}

test('TIMES-01 the form of a Draft with no Round Times has the switch off and two buttons', async ({
  page,
  context,
  setup,
}) => {
  await openNewDraftTimers(page, context, setup, openTimersFromIndex);

  await expectSwitch(timersSwitch(page), 'off');
  await expect(saveButton(page)).toBeVisible();
  await expect(cancelButton(page)).toBeVisible();
  await expect(roundTimeRows(page)).toHaveCount(0);
});

test('TIMES-02 turning on Use Pick Timers shows a row with a suggested value for each Round', async ({
  page,
  context,
  setup,
}) => {
  await openNewDraftTimers(page, context, setup);

  await turnOnTimers(page);

  await expectSwitch(timersSwitch(page), 'on');
  await expect(sameRoundTimeSwitch(page)).toBeVisible();
  await expect(page.getByText('Max Draft Length (estimated):')).toBeVisible();
  await expect(roundTimeRows(page)).toHaveCount(4);
  await expectTime(page, 0, { minutes: 4, seconds: 0 });
  await expectTime(page, 1, { minutes: 3, seconds: 30 });
  await expectTime(page, 2, { minutes: 3, seconds: 30 });
  await expectTime(page, 3, { minutes: 3, seconds: 15 });
});

test('TIMES-03 saving the values of two Rounds shows a toast, goes to the previous route, and keeps the values', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const { draft } = await openNewDraftTimers(page, context, setup, openTimersFromIndex);
  await turnOnTimers(page);
  await setTime(page, 0, { minutes: 2, seconds: 15 });
  await setTime(page, 2, { minutes: 1, seconds: 30 });

  await saveTimers(page);

  await expect(page).toHaveURL(indexRoute(draft));

  await openTimers(page, draft);
  await expectSwitch(timersSwitch(page), 'on');
  await expectTime(page, 0, { minutes: 2, seconds: 15 });
  await expectTime(page, 1, { minutes: 3, seconds: 30 });
  await expectTime(page, 2, { minutes: 1, seconds: 30 });
});

test('TIMES-04 One Timer for All Rounds saves one value that the Draft Board uses for each Pick', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft } = await openNewDraftTimers(page, context, setup);
  await turnOnTimers(page);

  await sameRoundTimeSwitch(page).click();

  await expectAllRow(page);
  await setTime(page, 0, { minutes: 1, seconds: 30 });
  await saveTimers(page);

  await openTimers(page, draft);
  await expectSwitch(sameRoundTimeSwitch(page), 'on');
  await expectAllRow(page);
  await expectTime(page, 0, { minutes: 1, seconds: 30 });

  await setup.addManagers(commissioner, draft, ['Ann', 'Bob']);
  await setup.setDraftStatus(commissioner, draft, 'In Progress');
  await openWithClock(page, boardRoute(draft), pickTimer(page));
  await expectFlipClockBetween(page, 85, 90);
});

test('TIMES-05 turning off Use Pick Timers makes the Draft Board show "Pick timers disabled"', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await setup.setRoundTimes(commissioner, draft, [{ seconds: 120 }]);
  await openTimers(page, draft);
  await expectSwitch(timersSwitch(page), 'on');

  await timersSwitch(page).click();
  await saveTimers(page);

  await page.goto(boardRoute(draft));
  await expect(pickTimerDisabled(page)).toHaveText('Pick timers disabled', VISIBLE_TEXT);
});

test('TIMES-06 a Complete Draft shows a warning toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06, W-14');
  const commissioner = await setup.logInNewCommissioner(context);
  const complete = await setup.createDraft(commissioner, { status: 'Complete' });
  const other = await setup.createDraft(commissioner, { status: 'In Progress' });
  const previousRoute = await openDraftScreen(page, other);

  await page.goto(timersRoute(complete));

  await expectRedirectToPreviousRoute(page, 'Draft is already completed', previousRoute);
});

test('TIMES-07 an In Progress Draft saves a changed value as in TIMES-03', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-06');
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await setup.setRoundTimes(commissioner, draft, [{ seconds: 120 }]);
  await openTimersFromIndex(page, draft);
  await expectSwitch(timersSwitch(page), 'on');
  await expectSwitch(sameRoundTimeSwitch(page), 'on');

  await setTime(page, 0, { minutes: 1, seconds: 45 });
  await saveTimers(page);

  await expect(page).toHaveURL(indexRoute(draft));

  await openTimers(page, draft);
  await expectTime(page, 0, { minutes: 1, seconds: 45 });
});

test('TIMES-08 Max Draft Length counts each Round once, and writes the zero seconds in words', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-20, W-34');
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { rounds: 2, managers: ['Ann', 'Bob', 'Cy', 'Di'] });
  await openTimers(page, draft);
  await turnOnTimers(page);

  await setTime(page, 0, { minutes: 1, seconds: 0 });
  await setTime(page, 1, { minutes: 1, seconds: 0 });

  await expect(maxDraftLength(page)).toHaveText('8 minutes, 0 seconds');

  await sameRoundTimeSwitch(page).click();

  await expect(maxDraftLength(page)).toHaveText('8 minutes, 0 seconds');
});

test('TIMES-09 an out-of-range value disables Save Timers and sends no request', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-18');
  const { draft } = await openNewDraftTimers(page, context, setup);
  await turnOnTimers(page);
  const saves = recordApiRequests(page, { method: 'POST', path: timersPath(draft) });

  await minutesField(page, 0).fill('16');
  await expect(saveButton(page)).toBeDisabled();
  // Playwright waits for a disabled button to be enabled before it clicks, so the click is forced
  await saveButton(page).click({ force: true });

  await minutesField(page, 0).fill('4');
  await expect(saveButton(page)).toBeEnabled();

  await secondsField(page, 0).fill('60');
  await expect(saveButton(page)).toBeDisabled();
  await saveButton(page).click({ force: true });

  await page.waitForLoadState('networkidle');
  expect(saves).toEqual([]);
});

test('TIMES-10 an API error for the save shows a toast, keeps the screen, and Cancel goes back', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await openNewDraftTimers(page, context, setup, openTimersFromIndex);
  await failApiRequest(page, { method: 'POST', path: timersPath(draft) });
  await turnOnTimers(page);

  await saveButton(page).click();

  await expectToast(page, {
    title: 'Unable to save timers',
    message: "Whoops! We hit a snag - looks like it's on our end (500)",
  });
  await expect(page).toHaveURL(timersRoute(draft));
  await expect(heading(page)).toBeVisible();

  await cancelButton(page).click();

  await expect(page).toHaveURL(indexRoute(draft));
});

test('TIMES-11 an anonymous viewer goes to the login screen', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-01');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner);

  await page.goto(timersRoute(draft));

  await expect(page).toHaveURL(/\/login$/);
});

test('TIMES-12 another Commissioner goes to the Draft index', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-02');
  const owner = await setup.registerCommissioner();
  const draft = await setup.createDraft(owner);
  await setup.logInNewCommissioner(context);

  await page.goto(timersRoute(draft));

  await expect(page).toHaveURL(indexRoute(draft));
});

test('TIMES-13 an API error for the Round Times shows an error text and no form', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-24');
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner);
  await failApiRequest(page, { method: 'GET', path: timersPath(draft) });

  await page.goto(timersRoute(draft));

  await expect(page.getByText('Unable to load draft defaults', { exact: true })).toBeVisible();
  await expect(timersSwitch(page)).toBeHidden();
  await expect(loadingText(page)).toBeHidden();
});
