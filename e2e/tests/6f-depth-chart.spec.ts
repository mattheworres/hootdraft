import { Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import {
  DRAFT_POLL_MS,
  DraftRoute,
  expectDraftStatusRedirect,
  openRoute,
  openWithClock,
  SETTING_UP_TOAST,
} from '../helpers/draft-screen';
import { failApiRequest, holdApiResponses, recordApiRequests, waitForApiResponse } from '../helpers/intercept';
import { expectFooterHidden } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { setDraftEndTime } from '../helpers/timestamps';
import { fullName, VISIBLE_TEXT } from '../helpers/text';
import { expectToast } from '../helpers/toast';

// Route group 6F of docs/flow-inventory.md. Each test name starts with the row ID.

const depthChartRoute: DraftRoute = (draft) => `/draft/${draft.id}/depth_chart`;

const POSITIONS = [
  { position: 'QB', slots: 1 },
  { position: 'RB', slots: 2 },
];
const MOVE_PATH = /^draft\/\d+\/pick\/\d+\/depth_chart\/\d+$/;

const managerMenu = (page: Page) => page.getByTestId('depth-chart-manager-menu');
const positionBoxes = (page: Page) => page.getByTestId('depth-chart-position').filter({ visible: true });
const positionBox = (page: Page, name: string) =>
  page.getByTestId('depth-chart-position').filter({ hasText: new RegExp(`^\\s*${name}\\s`) });
const picksIn = (page: Page, name: string) => positionBox(page, name).getByTestId('depth-chart-pick');
const positionDetail = (page: Page, name: string) => positionBox(page, name).getByTestId('depth-chart-position-detail');

/** The Depth Chart screen is ready when the list of Managers shows. */
const openDepthChart = (page: Page, draft: Draft) => openRoute(page, depthChartRoute(draft), managerMenu(page));

const openDepthChartWithClock = (page: Page, draft: Draft) =>
  openWithClock(page, depthChartRoute(draft), managerMenu(page));

/** An In Progress Draft of two Managers, with the Depth Chart positions QB and RB. */
const depthChartDraft = (setup: ApiSetup, commissioner: LoggedInUser) =>
  setup.createDraft(commissioner, { status: 'In Progress', style: 'standard', depthChartPositions: POSITIONS });

/**
 * A Complete Draft of one Round: each of the two Managers has one Selected Pick, with no position.
 * `createDraft` can make a Complete Draft, but it does not return the Picks, and the tests need their names.
 */
async function completeDepthChartDraft(setup: ApiSetup, commissioner: LoggedInUser) {
  const draft = await setup.createDraft(commissioner, {
    status: 'In Progress',
    style: 'standard',
    rounds: 1,
    depthChartPositions: POSITIONS,
  });
  const picks = await setup.addPicks(commissioner, draft, 2);

  return { draft, picks };
}

/** Drags the only Pick of "Unassigned" to the box of a position. */
const dragUnassignedTo = (page: Page, position: string) =>
  picksIn(page, 'Unassigned').dragTo(positionBox(page, position));

/** Drags the only Pick of "Unassigned" to the box of a position and waits until the API has stored the move. */
async function moveUnassignedTo(page: Page, position: string): Promise<void> {
  const move = waitForApiResponse(page, { method: 'PUT', path: MOVE_PATH });

  await dragUnassignedTo(page, position);

  await move;
}

test('DEPTH-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectDraftStatusRedirect(page, setup, {
    route: depthChartRoute,
    status: 'Setting Up',
    message: SETTING_UP_TOAST,
  });
});

test('DEPTH-02 the first Manager is chosen, and each position and "Unassigned" show their Picks', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const [assigned, secondManagerPick, unassigned] = await setup.addPicks(commissioner, draft, 3);
  await setup.assignPickToPosition(commissioner, draft, assigned, 'QB');
  await setup.assignPickToPosition(commissioner, draft, secondManagerPick, 'RB');

  await openDepthChart(page, draft);

  await expect(page.getByRole('heading', { name: 'Team:' })).toBeVisible();
  await expect(managerMenu(page).getByRole('option')).toHaveText(draft.managers.map((manager) => manager.name));
  await expect(managerMenu(page).getByRole('option', { selected: true })).toHaveText(draft.managers[0].name);
  await expect(positionBoxes(page)).toHaveCount(3);
  await expect(positionBox(page, 'QB')).toContainText('1/1', VISIBLE_TEXT);
  await expect(picksIn(page, 'QB')).toContainText(fullName(assigned), VISIBLE_TEXT);
  await expect(positionBox(page, 'RB')).toContainText('0/2', VISIBLE_TEXT);
  await expect(picksIn(page, 'Unassigned')).toContainText(fullName(unassigned), VISIBLE_TEXT);
  await expect(positionBox(page, 'Unassigned')).not.toContainText('/', VISIBLE_TEXT);
  await expectFooterHidden(page);

  await managerMenu(page).selectOption({ label: draft.managers[1].name });

  await expect(positionBoxes(page)).toHaveCount(2);
  await expect(positionBox(page, 'Unassigned')).toBeHidden();
});

test('DEPTH-03 the chosen Manager stays chosen after a visit to the Draft index', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const [, secondManagerPick] = await setup.addPicks(commissioner, draft, 2);
  await setup.assignPickToPosition(commissioner, draft, secondManagerPick, 'RB');
  const second = draft.managers[1];
  await openDepthChart(page, draft);

  await managerMenu(page).selectOption({ label: second.name });

  await expect(picksIn(page, 'RB')).toContainText(secondManagerPick.lastName, VISIBLE_TEXT);
  await expect(positionBox(page, 'Unassigned')).toBeHidden();

  await page.getByRole('link', { name: draft.name }).click();
  await expect(page).toHaveURL(`/draft/${draft.id}`);
  await page.getByRole('link', { name: 'Depth Charts' }).click();

  await expect(managerMenu(page).getByRole('option', { selected: true })).toHaveText(second.name);
  await expect(picksIn(page, 'RB')).toContainText(secondManagerPick.lastName, VISIBLE_TEXT);
});

test('DEPTH-04 an anonymous viewer drags a Pick to a position, and the Pick stays after a reload', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const [pick] = await setup.addPicks(commissioner, draft, 1);
  await openDepthChart(page, draft);
  await expect(picksIn(page, 'Unassigned')).toContainText(fullName(pick), VISIBLE_TEXT);

  await moveUnassignedTo(page, 'QB');

  await expect(picksIn(page, 'QB')).toContainText(fullName(pick), VISIBLE_TEXT);
  await expect(positionBox(page, 'QB')).toContainText('1/1', VISIBLE_TEXT);
  await expect(positionBox(page, 'Unassigned')).toBeHidden();

  await page.reload();
  await expect(managerMenu(page)).toBeVisible();

  await expect(picksIn(page, 'QB')).toContainText(fullName(pick), VISIBLE_TEXT);
  await expect(positionBox(page, 'QB')).toContainText('1/1', VISIBLE_TEXT);
});

test('DEPTH-05 a box with one slot takes a second Pick and shows "2/1" in the over-full style', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const [first, , second] = await setup.addPicks(commissioner, draft, 3);
  await setup.assignPickToPosition(commissioner, draft, first, 'QB');
  await openDepthChart(page, draft);
  const fullClass = await positionDetail(page, 'QB').getAttribute('class');

  await moveUnassignedTo(page, 'QB');

  await expect(positionBox(page, 'QB')).toContainText('2/1', VISIBLE_TEXT);
  await expect(picksIn(page, 'QB')).toHaveCount(2);
  await expect(picksIn(page, 'QB')).toContainText([fullName(first), fullName(second)], VISIBLE_TEXT);
  await expect(positionDetail(page, 'QB')).toHaveClass(/position-over-full/);
  expect(fullClass).not.toContain('position-over-full');
});

test('DEPTH-06 a Pick moves in a Draft that became Complete less than 10 minutes ago', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDepthChartDraft(setup, commissioner);
  await setDraftEndTime(draft.id, { minutes: 5 });
  await openDepthChart(page, draft);

  await moveUnassignedTo(page, 'QB');

  await expect(picksIn(page, 'QB')).toContainText(fullName(picks[0]), VISIBLE_TEXT);
  await expect(positionBox(page, 'QB')).toContainText('1/1', VISIBLE_TEXT);
});

test('DEPTH-07 a Pick does not move in a Draft that became Complete more than 10 minutes ago', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDepthChartDraft(setup, commissioner);
  await setDraftEndTime(draft.id, { minutes: 11 });
  await openDepthChart(page, draft);
  const moves = recordApiRequests(page, { method: 'PUT', path: MOVE_PATH });

  await dragUnassignedTo(page, 'QB');

  await expect(picksIn(page, 'Unassigned')).toContainText(fullName(picks[0]), VISIBLE_TEXT);
  await expect(positionBox(page, 'QB')).toContainText('0/1', VISIBLE_TEXT);

  // A move would be sent on drop, before this request, so its answer bounds the wait for one
  const nextManagerChart = waitForApiResponse(page, {
    method: 'GET',
    path: `draft/${draft.id}/manager/${draft.managers[1].id}/depth_chart`,
  });
  await managerMenu(page).selectOption({ label: draft.managers[1].name });
  await nextManagerChart;
  expect(moves).toEqual([]);
});

test('DEPTH-08 an API error shows a toast for the Managers, the Depth Chart, and a move', async ({
  page,
  newViewerPage,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const [pick] = await setup.addPicks(commissioner, draft, 1);
  const [first] = draft.managers;

  await failApiRequest(page, { method: 'GET', path: `draft/${draft.id}/managers` });
  await page.goto(depthChartRoute(draft));
  await expectToast(page, { message: 'Unable to load managers' });

  const chartViewer = await newViewerPage();
  await failApiRequest(chartViewer, { method: 'GET', path: `draft/${draft.id}/manager/${first.id}/depth_chart` });
  await chartViewer.goto(depthChartRoute(draft));
  await expectToast(chartViewer, { message: 'Unable to load depth chart.' });

  const moveViewer = await newViewerPage();
  await failApiRequest(moveViewer, { method: 'PUT', path: MOVE_PATH });
  await openDepthChart(moveViewer, draft);
  await expect(picksIn(moveViewer, 'Unassigned')).toContainText(fullName(pick), VISIBLE_TEXT);
  await dragUnassignedTo(moveViewer, 'QB');
  await expectToast(moveViewer, { message: 'Unable to update depth chart.' });
});

test('DEPTH-09 a Draft without Depth Charts has no link, and the direct URL goes to the Draft index', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-30');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await page.goto(depthChartRoute(draft));

  await expect(page).toHaveURL(`/draft/${draft.id}`);
  // The Draft navigation shows once the Draft has loaded, so the link check does not pass before the screen renders
  await expect(page.getByRole('link', { name: draft.name })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Depth Charts' })).toBeHidden();
});

test('DEPTH-10 a second viewer sees a new Selected Pick in "Unassigned" after the next poll', async ({
  newViewerPage,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const viewer = await newViewerPage();
  await openDepthChartWithClock(viewer, draft);
  await expect(positionBoxes(viewer)).toHaveCount(2);
  await expect(positionBox(viewer, 'Unassigned')).toBeHidden();

  const pick = await setup.addPick(commissioner, draft);
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(picksIn(viewer, 'Unassigned')).toContainText(fullName(pick), VISIBLE_TEXT);
});

test('DEPTH-11 a slow Managers request shows a loading indicator and no list of Managers', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-24');
  const commissioner = await setup.registerCommissioner();
  const draft = await depthChartDraft(setup, commissioner);
  const release = await holdApiResponses(page, { method: 'GET', path: `draft/${draft.id}/managers` });

  await page.goto(depthChartRoute(draft));

  try {
    await expect(page.getByText('Loading...')).toBeVisible();
    await expect(managerMenu(page)).toBeHidden();
  } finally {
    release();
  }
  await expect(managerMenu(page)).toBeVisible();
  await expect(page.getByText('Loading...')).toBeHidden();
});
