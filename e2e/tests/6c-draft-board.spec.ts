import { Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser, SelectedPick, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import {
  boardRoute,
  expectFlipClockBetween,
  flipClockSeconds,
  pickTimer,
  pickTimerDisabled,
} from '../helpers/draft-board';
import {
  DRAFT_POLL_MS,
  expectNoDraftPolls,
  expectRedirectToPreviousRoute,
  openDraftScreen,
  openDraftScreenWithClock,
  openRoute,
  openWithClock,
  SETTING_UP_TOAST,
} from '../helpers/draft-screen';
import { failApiRequest, holdApiResponses } from '../helpers/intercept';
import { expectFooterHidden } from '../helpers/navigation';
import { API_URL } from '../helpers/stack';
import { expect, test } from '../helpers/test';
import { fullName, VISIBLE_TEXT } from '../helpers/text';
import { setDraftStartTime, setPickTime } from '../helpers/timestamps';
import { expectToast } from '../helpers/toast';

const PHONE = { width: 375, height: 700 };
const POSITION_NAMES: Record<string, string> = { QB: 'Quarterback', RB: 'Runningback', WR: 'Wide Receiver' };

const boardRounds = (page: Page) => page.getByTestId('board-round');
const boardPicks = (page: Page) => page.getByTestId('board-pick');
const header = (page: Page) => page.getByTestId('board-header');
const currentPick = (page: Page) => header(page).getByTestId('current-pick');
const lastPick = (page: Page) => header(page).getByTestId('last-pick');
const pickTimerUp = (page: Page) => page.getByTestId('pick-timer-up');

const openBoard = (page: Page, draft: Draft) => openRoute(page, boardRoute(draft), boardPicks(page).first());
const openBoardWithClock = (page: Page, draft: Draft) =>
  openWithClock(page, boardRoute(draft), boardPicks(page).first());

async function expectText(locator: Locator, parts: (string | number)[]): Promise<void> {
  for (const part of parts) {
    await expect(locator).toContainText(String(part), VISIBLE_TEXT);
  }
}

async function expectSelectedPickCell(cell: Locator, pick: SelectedPick, managerName: string): Promise<void> {
  await expectText(cell, [
    pick.number,
    pick.firstName,
    pick.lastName,
    managerName,
    POSITION_NAMES[pick.position],
    pick.team,
  ]);
}

/** The colors of the positions come from the stylesheet of the API, as hex codes. */
async function expectPositionColor(page: Page, cell: Locator, position: string): Promise<void> {
  const stylesheet = await (await page.request.get(`${API_URL}style`)).text();
  const hex = stylesheet.match(new RegExp(`div\\.pick${position}\\{background-color:#([0-9A-Fa-f]{6});\\}`))?.[1];

  if (!hex) {
    throw new Error(`The stylesheet of the API has no color for the position ${position}`);
  }

  const [red, green, blue] = [0, 2, 4].map((start) => parseInt(hex.slice(start, start + 2), 16));

  await expect(cell).toHaveCSS('background-color', `rgb(${red}, ${green}, ${blue})`);
}

function pickCellPattern(pickNumber: number, managerName: string): RegExp {
  return new RegExp(`${pickNumber}\\w\\w\\s+${managerName}`);
}

async function expectTimeUp(page: Page, managerName: string): Promise<void> {
  await expect(pickTimerUp(page)).toContainText("Time's up!", VISIBLE_TEXT);
  await expect(pickTimerUp(page)).toContainText(`Everyone boo ${managerName}!`, VISIBLE_TEXT);
}

/** An In Progress Draft with a Round Time of one length for each Round. */
async function timedDraft(setup: ApiSetup, commissioner: LoggedInUser, seconds: number, rounds = 2): Promise<Draft> {
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds });
  await setup.setRoundTimes(commissioner, draft, [{ seconds }]);

  return draft;
}

test('BOARD-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');
  const commissioner = await setup.registerCommissioner();
  const settingUp = await setup.createDraft(commissioner);
  const other = await setup.createDraft(commissioner, { status: 'In Progress' });

  const previousRoute = await openDraftScreen(page, other);
  await page.goto(boardRoute(settingUp));

  await expectRedirectToPreviousRoute(page, SETTING_UP_TOAST, previousRoute);
});

test('BOARD-02 an In Progress Draft shows a row for each Round and a cell for each Pick', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 2 });
  const [first, second] = draft.managers;
  const selected = await setup.addPick(commissioner, draft, { position: 'RB', team: 'CHI' });
  const selectedOther = await setup.addPick(commissioner, draft, { position: 'QB', team: 'GNB' });
  const releasePicks = await holdApiResponses(page, { path: `draft/${draft.id}/picks` });

  await page.goto(boardRoute(draft));

  await expect(page.getByText('Loading the board, one sec.')).toBeVisible();
  releasePicks();
  await expect(boardPicks(page).first()).toBeVisible();
  await expect(page.getByText('Loading the board, one sec.')).toBeHidden();
  await expect(boardRounds(page)).toHaveCount(2);
  await expect(boardPicks(page)).toHaveCount(4);

  const [firstCell, secondCell, thirdCell, fourthCell] = await boardPicks(page).all();
  await expectSelectedPickCell(firstCell, selected, first.name);
  await expectSelectedPickCell(secondCell, selectedOther, second.name);
  // Round 2 of a Serpentine Draft: the Manager of the last column picks first
  await expectText(thirdCell, ['4th', first.name]);
  await expect(thirdCell).not.toContainText('On the clock', VISIBLE_TEXT);
  await expectText(fourthCell, ['3rd', second.name, 'On the clock']);
  await expectFooterHidden(page);
  await expectPositionColor(page, firstCell, selected.position);
  await expectPositionColor(page, secondCell, selectedOther.position);
});

test('BOARD-03 a Standard Draft keeps the Draft Order and a Serpentine Draft reverses Round 2', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const names = [unique('Ann'), unique('Bob'), unique('Cat')];
  const standard = await setup.createDraft(commissioner, {
    status: 'In Progress',
    style: 'standard',
    managers: names,
  });
  const serpentine = await setup.createDraft(commissioner, {
    status: 'In Progress',
    style: 'serpentine',
    managers: names,
  });

  await openBoard(page, standard);
  await expect(boardPicks(page)).toHaveCount(6);
  await expect(boardPicks(page)).toContainText(
    [
      ...names.map((name, index) => pickCellPattern(index + 1, name)),
      ...names.map((name, index) => pickCellPattern(names.length + index + 1, name)),
    ],
    VISIBLE_TEXT,
  );

  await openBoard(page, serpentine);
  await expect(boardPicks(page)).toHaveCount(6);
  await expect(boardPicks(page)).toContainText(
    [
      ...names.map((name, index) => pickCellPattern(index + 1, name)),
      ...names.map((name, index) => pickCellPattern(2 * names.length - index, name)),
    ],
    VISIBLE_TEXT,
  );
});

test('BOARD-04 the cell of a Traded Pick shows the new Manager', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const [first, second] = draft.managers;
  const onTheClock = await setup.currentPick(commissioner, draft);
  await setup.addTrade(commissioner, draft, { manager1Id: first.id, manager2Id: second.id, pickIds: [onTheClock.id] });

  await openBoard(page, draft);

  await expect(boardPicks(page).first()).toContainText(second.name, VISIBLE_TEXT);
  await expect(boardPicks(page).first()).not.toContainText(first.name, VISIBLE_TEXT);
});

test('BOARD-05 the header shows the current and last Pick, and hides on a phone-width window', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await openBoard(page, draft);

  await expect(header(page)).toContainText('Current Pick', VISIBLE_TEXT);
  await expectText(currentPick(page), ['Pick # 1', 'On the clock']);
  await expect(pickTimerDisabled(page)).toBeVisible();
  await expect(header(page)).not.toContainText('Last Pick', VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft);
  await openBoard(page, draft);

  await expect(header(page)).toContainText('Last Pick', VISIBLE_TEXT);
  await expectText(lastPick(page), [fullName(pick), 'Pick # 1']);
  await expect(currentPick(page)).toContainText('Pick # 2', VISIBLE_TEXT);

  await page.setViewportSize(PHONE);

  await expect(header(page)).toBeHidden();
});

test('BOARD-06 an In Progress Draft with no Round Times shows "Pick timers disabled"', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await openBoard(page, draft);

  await expect(pickTimerDisabled(page)).toHaveText('Pick timers disabled', VISIBLE_TEXT);
  await expect(pickTimer(page)).toBeHidden();
});

test('BOARD-07 the flip clock shows the remaining time and counts down', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await timedDraft(setup, commissioner, 600);
  await setDraftStartTime(draft.id, { seconds: 540 });

  await openBoardWithClock(page, draft);

  // The API measures on the database clock, so allow a few seconds around the 60 that remain
  await expectFlipClockBetween(page, 52, 60);
  const before = await flipClockSeconds(page);

  await page.clock.runFor(10_000);

  await expect.poll(() => flipClockSeconds(page)).toBeLessThanOrEqual(before - 9);
});

test('BOARD-08 the flip clock is replaced by "Time\'s up!" when the pick timer reaches zero', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await timedDraft(setup, commissioner, 600);
  await setDraftStartTime(draft.id, { seconds: 595 });
  await openBoardWithClock(page, draft);
  await expect(pickTimer(page)).toBeVisible();

  await page.clock.runFor(10_000);

  await expectTimeUp(page, draft.managers[0].name);
  await expect(pickTimer(page)).toBeHidden();
});

test('BOARD-09 a Pick whose time is over shows "Time\'s up!" at once', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await timedDraft(setup, commissioner, 60);
  await setDraftStartTime(draft.id, { seconds: 120 });

  await openBoard(page, draft);

  await expectTimeUp(page, draft.managers[0].name);
});

test('BOARD-10 a Complete Draft shows each Selected Pick, no header, and no polling', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 2 });
  const picks = await setup.addPicks(commissioner, draft, 4);
  await openBoardWithClock(page, draft);

  await expect(boardPicks(page)).toHaveCount(4);
  for (const pick of picks) {
    const manager = draft.managers.find(({ id }) => id === pick.managerId)!;
    await expectSelectedPickCell(boardPicks(page).filter({ hasText: pick.lastName }), pick, manager.name);
  }
  await expect(header(page)).toBeHidden();
  await expectNoDraftPolls(page, draft);
});

test('BOARD-11 an API error on the Picks request gives a toast', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await failApiRequest(page, { path: `draft/${draft.id}/picks` });

  await page.goto(boardRoute(draft));

  await expectToast(page, { message: 'Unable to load draft board' });
});

test('BOARD-12 API errors on the current and last Pick, updated Picks, and remaining time give toasts', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await timedDraft(setup, commissioner, 600);

  await failApiRequest(page, { path: `draft/${draft.id}/picks/next` });
  await page.goto(boardRoute(draft));
  await expectToast(page, { message: 'Unable to get next and/or current picks' });
  await page.unrouteAll();

  await failApiRequest(page, { path: `draft/${draft.id}/timer/remaining` });
  await page.goto(boardRoute(draft));
  await expectToast(page, { message: 'Unable to load remaining pick time' });
  await page.unrouteAll();

  await openBoardWithClock(page, draft);
  await failApiRequest(page, { path: `draft/${draft.id}/picks/updated` });
  await setup.addPick(commissioner, draft);
  await page.clock.runFor(DRAFT_POLL_MS);
  await expectToast(page, { message: 'Unable to get up to date draft picks' });
});

test('BOARD-13 a second viewer sees a new Pick after the next poll', async ({ newViewerPage, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const viewer = await newViewerPage();
  await openBoardWithClock(viewer, draft);
  await expect(currentPick(viewer)).toContainText('Pick # 1', VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(boardPicks(viewer).first()).toContainText(fullName(pick), VISIBLE_TEXT);
  await expect(boardPicks(viewer).nth(1)).toContainText('On the clock', VISIBLE_TEXT);
  await expect(currentPick(viewer)).toContainText('Pick # 2', VISIBLE_TEXT);
  await expectText(lastPick(viewer), [fullName(pick), 'Pick # 1']);
});

test('BOARD-14 a second viewer sees an edited Pick after the next poll', async ({ newViewerPage, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const pick = await setup.addPick(commissioner, draft);
  const viewer = await newViewerPage();
  await openBoardWithClock(viewer, draft);
  await expect(boardPicks(viewer).first()).toContainText(pick.lastName, VISIBLE_TEXT);

  const edited = { ...pick, firstName: 'Edited', lastName: unique('Name'), team: 'CHI', position: 'RB' };
  await setup.editPick(commissioner, draft, edited);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expectText(boardPicks(viewer).first(), [edited.firstName, edited.lastName, 'Runningback', 'CHI']);
});

test('BOARD-15 a second viewer sees the new Managers of a Trade after the next poll', async ({
  newViewerPage,
  setup,
}) => {
  knownFailureOnAngularJs('W-40');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const [first, second] = draft.managers;
  const pickIds = await setup.upcomingPickIds(commissioner, draft, 2);
  const viewer = await newViewerPage();
  await openBoardWithClock(viewer, draft);
  await expect(boardPicks(viewer).first()).toContainText(first.name, VISIBLE_TEXT);

  await setup.addTrade(commissioner, draft, { manager1Id: first.id, manager2Id: second.id, pickIds });
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(boardPicks(viewer).first()).toContainText(second.name, VISIBLE_TEXT);
  await expect(boardPicks(viewer).nth(1)).toContainText(first.name, VISIBLE_TEXT);
});

test('BOARD-16 two flip clocks show the same time, and each starts again after a Pick', async ({
  newViewerPage,
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 2 });
  await setup.setRoundTimes(commissioner, draft, [
    { round: 1, seconds: 600 },
    { round: 2, seconds: 300 },
  ]);
  const firstPick = await setup.addPick(commissioner, draft);
  await setPickTime(firstPick.id, { seconds: 120 });
  const viewer = await newViewerPage();
  await openBoardWithClock(page, draft);
  await openBoardWithClock(viewer, draft);
  await expect(pickTimer(page)).toBeVisible();
  await expect(pickTimer(viewer)).toBeVisible();

  const [pageSeconds, viewerSeconds] = [await flipClockSeconds(page), await flipClockSeconds(viewer)];
  expect(Math.abs(pageSeconds - viewerSeconds)).toBeLessThanOrEqual(2);
  expect(pageSeconds).toBeLessThanOrEqual(480);
  expect(pageSeconds).toBeGreaterThan(470);

  await setup.addPick(commissioner, draft);
  await page.clock.runFor(DRAFT_POLL_MS);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  for (const board of [page, viewer]) {
    await expectFlipClockBetween(board, 290, 300);
  }
});

test('BOARD-17 after the last Pick a second viewer sees the Selected Pick, no header, and no polling', async ({
  newViewerPage,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 1 });
  await setup.addPick(commissioner, draft);
  const viewer = await newViewerPage();
  await openBoardWithClock(viewer, draft);
  await expect(header(viewer)).toBeVisible();

  const last = await setup.addPick(commissioner, draft);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(boardPicks(viewer).last()).toContainText(fullName(last), VISIBLE_TEXT);
  await expect(header(viewer)).toBeHidden();
  await expectNoDraftPolls(viewer, draft);
});

test('BOARD-18 after a reset a second viewer gets the result of BOARD-01', async ({ newViewerPage, setup }) => {
  knownFailureOnAngularJs('W-39');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const other = await setup.createDraft(commissioner, { status: 'In Progress' });
  const viewer = await newViewerPage();
  const previousRoute = await openDraftScreenWithClock(viewer, other);
  await openBoard(viewer, draft);

  await setup.setDraftStatus(commissioner, draft, 'Setting Up');
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expectRedirectToPreviousRoute(viewer, SETTING_UP_TOAST, previousRoute);
});
