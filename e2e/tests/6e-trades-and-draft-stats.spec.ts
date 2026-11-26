import { Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import {
  DRAFT_POLL_MS,
  DraftRoute,
  expectDraftStatusRedirect,
  openRoute,
  openWithClock,
  SETTING_UP_TOAST,
  visiblePanel,
} from '../helpers/draft-screen';
import { fulfillApiRequest } from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { VISIBLE_TEXT } from '../helpers/text';

// Route group 6E of docs/flow-inventory.md. Each test name starts with the row ID.

const NOT_COMPLETE_TOAST = 'Draft is not complete yet';
const NO_TRADES = 'No trades have been made.';
const NO_STATS = 'Statistics have not been generated for this draft.';

const tradesRoute: DraftRoute = (draft) => `/draft/${draft.id}/trades`;
const statsRoute: DraftRoute = (draft) => `/draft/${draft.id}/stats`;

const tradesPanel = (page: Page) => page.getByTestId('trades-panel');
const tradesHeading = (page: Page) => tradesPanel(page).getByRole('heading', { name: /Trades$/ });
const tradeCards = (page: Page) => page.getByTestId('trade').filter({ visible: true });
const tradeAssets = (trade: Locator) => trade.getByTestId('trade-asset');
const statsPanel = (page: Page) => page.getByTestId('draft-stats-panel');
const statsHeading = (page: Page) => statsPanel(page).getByRole('heading', { name: /Draft Statistics$/ });
const statRows = (page: Page) => page.getByTestId('draft-stat');

/** An In Progress Draft of two Managers who pick in turn: Pick 1 is the first Manager's, Pick 2 the second's. */
function standardDraft(setup: ApiSetup, commissioner: LoggedInUser, rounds = 2): Promise<Draft> {
  return setup.createDraft(commissioner, { status: 'In Progress', style: 'standard', rounds });
}

/** A Complete Draft of one Round and two Managers. */
const completeDraft = (setup: ApiSetup, commissioner: LoggedInUser) =>
  setup.createDraft(commissioner, { status: 'Complete', style: 'standard', rounds: 1 });

/**
 * The Trade of TRADES-03: the first Manager gives the first Pick, which is Selected, for the second Pick, which is
 * Unselected. Returns the names of the two Managers.
 */
async function tradeSelectedForUnselected(setup: ApiSetup, commissioner: LoggedInUser, draft: Draft) {
  const [first, second] = draft.managers;
  const selected = await setup.addPick(commissioner, draft, { firstName: 'Davante', lastName: 'Adams' });
  const [unselected] = await setup.upcomingPickIds(commissioner, draft, 1);
  await setup.addTrade(commissioner, draft, {
    manager1Id: first.id,
    manager2Id: second.id,
    pickIds: [selected.id, unselected],
  });

  return { first: first.name, second: second.name };
}

const SELECTED_ASSET = /^Pick #\s*1\s+Round\s*1\s+Davante Adams\s+Wide Receiver – Green Bay Packers$/;

function tradeText(first: string, second: string): RegExp {
  return new RegExp(
    [
      `Trade between ${first} and ${second}:`,
      '\\d{1,2}:\\d{2} [AP]M',
      '\\(during the 1st round\\)',
      `${first} gets:`,
      '1st Round Pick \\(2nd overall\\)',
      `${second} gets:`,
    ].join('[\\s\\S]*'),
  );
}

/** Answers the Draft Stats request of the Draft with this `draft_statistics` value: the numbers, or null for none. */
const fulfillDraftStats = (page: Page, draft: Pick<Draft, 'id'>, draftStatistics: object | null) =>
  fulfillApiRequest(
    page,
    { method: 'GET', path: `draft/${draft.id}/stats` },
    { success: true, errors: [], draft_statistics: draftStatistics },
  );

const escapeRegExp = (text: string) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/** A Draft Stat row: the label, then the value. The screen puts them on two lines. */
const stat = (label: string, value: string) => new RegExp(`^${label}:\\s+${escapeRegExp(value)}$`);

/** Asserts that `right` sits to the right of `left`, with their tops level. */
async function expectSideBySide(left: Locator, right: Locator): Promise<void> {
  const leftBox = (await left.boundingBox())!;
  const rightBox = (await right.boundingBox())!;

  expect(rightBox.x).toBeGreaterThanOrEqual(leftBox.x + leftBox.width - 1);
  expect(Math.abs(rightBox.y - leftBox.y)).toBeLessThan(1);
}

// Trades

test('TRADES-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectDraftStatusRedirect(page, setup, {
    route: tradesRoute,
    status: 'Setting Up',
    message: SETTING_UP_TOAST,
  });
});

test('TRADES-02 an In Progress Draft with no Trade says so', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);

  await openRoute(page, tradesRoute(draft), tradesHeading(page));

  await expect(tradesPanel(page)).toContainText(NO_TRADES, VISIBLE_TEXT);
  await expect(tradeCards(page)).toHaveCount(0);
});

test('TRADES-03 a Trade lists what each Manager gets, a Selected Pick and an Unselected Pick', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const { first, second } = await tradeSelectedForUnselected(setup, commissioner, draft);

  await openRoute(page, tradesRoute(draft), tradesHeading(page));

  await expect(tradeCards(page)).toHaveCount(1);
  await expect(tradeCards(page)).toContainText(tradeText(first, second), VISIBLE_TEXT);
  await expect(tradeAssets(tradeCards(page))).toHaveText(
    ['1st Round Pick (2nd overall)', SELECTED_ASSET],
    VISIBLE_TEXT,
  );
  await expect(tradesPanel(page)).not.toContainText(NO_TRADES, VISIBLE_TEXT);
});

test('TRADES-04 a Trade Asset that was an Unselected Pick shows the Pick it became, also in a Complete Draft', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  await tradeSelectedForUnselected(setup, commissioner, draft);
  await setup.addPick(commissioner, draft, { firstName: 'Next', lastName: 'Guy' });
  const became = '1st Round Pick (2nd overall) became Next Guy, WR – GNB';

  await openRoute(page, tradesRoute(draft), tradesHeading(page));

  await expect(tradeAssets(tradeCards(page))).toHaveText([became, SELECTED_ASSET], VISIBLE_TEXT);

  await setup.addPicks(commissioner, draft, 2);
  expect(await setup.draftStatus(commissioner, draft)).toBe('Complete');
  await page.reload();

  await expect(tradesHeading(page)).toBeVisible();
  await expect(tradeAssets(tradeCards(page))).toHaveText([became, SELECTED_ASSET], VISIBLE_TEXT);
});

test('TRADES-05 a second viewer sees a new Trade after the next poll', async ({ newViewerPage, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await standardDraft(setup, commissioner);
  const [first, second] = draft.managers;
  const viewer = await newViewerPage();
  await openWithClock(viewer, tradesRoute(draft), tradesHeading(viewer));
  await expect(tradesPanel(viewer)).toContainText(NO_TRADES, VISIBLE_TEXT);

  const pickIds = await setup.upcomingPickIds(commissioner, draft, 2);
  await setup.addTrade(commissioner, draft, { manager1Id: first.id, manager2Id: second.id, pickIds });
  await viewer.clock.runFor(DRAFT_POLL_MS);

  await expect(tradeCards(viewer)).toHaveCount(1);
  await expect(tradeCards(viewer)).toContainText(`Trade between ${first.name} and ${second.name}:`, VISIBLE_TEXT);
});

// Draft Stats

test('STATS-01 a Setting Up Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06');

  await expectDraftStatusRedirect(page, setup, {
    route: statsRoute,
    status: 'Setting Up',
    message: SETTING_UP_TOAST,
  });
});

test('STATS-02 an In Progress Draft gives a warning toast and goes to the previous route', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-06, W-14');

  await expectDraftStatusRedirect(page, setup, {
    route: statsRoute,
    status: 'In Progress',
    message: NOT_COMPLETE_TOAST,
  });
});

test('STATS-03 the Draft Stats show each value, with each duration in words, next to League Details', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-34');
  const commissioner = await setup.registerCommissioner();
  const draft = await completeDraft(setup, commissioner);
  const [first, second] = draft.managers;
  // The API measures each duration against the wall clock, so the test sets the exact values that the screen shows.
  // The durations include 0 and 60 seconds, the two values that W-34 words wrongly.
  await fulfillDraftStats(page, draft, {
    drafting_time_seconds: 125,
    longest_avg_pick_manager_name: first.name,
    longest_avg_pick_seconds: 90,
    shortest_avg_pick_manager_name: second.name,
    shortest_avg_pick_seconds: 10,
    longest_single_pick_manager_name: first.name,
    longest_single_pick_seconds: 125,
    shortest_single_pick_manager_name: second.name,
    shortest_single_pick_seconds: 0,
    average_pick_seconds: 50,
    longest_round: 2,
    longest_round_seconds: 60,
    shortest_round: 1,
    shortest_round_seconds: 45,
    most_drafted_team: 'Green Bay Packers',
    most_drafted_team_count: 3,
    least_drafted_team: 'Dallas Cowboys',
    least_drafted_team_count: 2,
    most_drafted_position: 'Wide Receiver',
    most_drafted_position_count: 3,
    least_drafted_position: 'Quarterback',
    least_drafted_position_count: 2,
  });

  await openRoute(page, statsRoute(draft), statsHeading(page));

  // Before the stat text, which fails on AngularJS for W-34, so that the layout is checked on both clients.
  const leagueDetails = visiblePanel(page, 'league-details-panel');
  await expect(leagueDetails.getByRole('heading', { name: `League Details: ${draft.name}` })).toBeVisible();
  await expectSideBySide(statsPanel(page), leagueDetails);
  await expect(statRows(page)).toHaveText(
    [
      stat('Time Spent Drafting', '2 minutes, 5 seconds'),
      stat('Longest Average Pick', `1 minute, 30 seconds (${first.name})`),
      stat('Shortest Average Pick', `10 seconds (${second.name})`),
      stat('Longest Single Pick', `2 minutes, 5 seconds (${first.name})`),
      stat('Shortest Single Pick', `0 seconds (${second.name})`),
      stat('Average Pick', '50 seconds'),
      stat('Longest Round', '1 minute (2nd round)'),
      stat('Shortest Round', '45 seconds (1st round)'),
      stat('Most Drafted Team', 'Green Bay Packers (3 times)'),
      stat('Least Drafted Team', 'Dallas Cowboys (2 times)'),
      stat('Most Drafted Position', 'Wide Receiver (3 times)'),
      stat('Least Drafted Position', 'Quarterback (2 times)'),
    ],
    VISIBLE_TEXT,
  );
});

test('STATS-04 a Complete Draft with no Draft Stats says so', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await completeDraft(setup, commissioner);
  // The API makes the Draft Stats with the last Pick, so no setup call leaves a Complete Draft without them.
  await fulfillDraftStats(page, draft, null);

  await openRoute(page, statsRoute(draft), statsHeading(page));

  await expect(statsPanel(page)).toContainText(NO_STATS, VISIBLE_TEXT);
  await expect(statRows(page).filter({ visible: true })).toHaveCount(0);
});
