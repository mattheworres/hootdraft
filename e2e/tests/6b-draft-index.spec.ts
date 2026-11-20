import { Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser, PickEntry, SelectedPick, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import {
  DRAFT_POLL_MS,
  expectNoDraftPolls,
  openDraftScreen,
  openDraftScreenLoggedIn,
  openDraftScreenWithClock,
} from '../helpers/draft-screen';
import { failApiRequest } from '../helpers/intercept';
import { expectMenuLinksOpenRoutes } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { expectToast } from '../helpers/toast';

// Route group 6B of docs/flow-inventory.md. Each test name starts with the row ID.

const DONATION_HIDE_DAYS = 14;
// The views hold hidden text, such as the error text of each panel and "On the clock" of each upcoming Pick
const VISIBLE_TEXT = { useInnerText: true };
const DATE_TIME = /\w+day, \w+ \d+\w\w \d{4}, \d+:\d\d [ap]m/;

// Each Draft status has its own view with its own panels, and the views of the other statuses are hidden
const visiblePanel = (page: Page, testId: string) => page.getByTestId(testId).filter({ visible: true });
const leagueDetails = (page: Page) => visiblePanel(page, 'league-details-panel');
const commishActions = (page: Page) => visiblePanel(page, 'commish-actions-panel');
const draftOrder = (page: Page) => visiblePanel(page, 'draft-order-panel');
const upcomingPicks = (page: Page) => visiblePanel(page, 'upcoming-picks-panel');
const lastPicks = (page: Page) => visiblePanel(page, 'last-picks-panel');
const roundPicks = (page: Page) => visiblePanel(page, 'round-picks-panel');
const leagueDetailsHeading = (page: Page, draft: Draft) =>
  leagueDetails(page).getByRole('heading', { name: `League Details: ${draft.name}` });
const roundPicksHeading = (page: Page, round: number) => page.getByRole('heading', { name: `Round ${round} Picks` });
const pickRows = (panel: Locator) => panel.getByTestId('pick-row');
const nextPick = (page: Page) => pickRows(upcomingPicks(page)).first();
const donationMessage = (page: Page) => page.getByTestId('donation-message');
const commishActionEntries = (page: Page) =>
  commishActions(page).getByRole('link').or(commishActions(page).getByRole('button'));
const pickNumber = (number: number) => new RegExp(`Pick #\\s*${number}\\b`);

/** Each [label, value] pair of the "League Details" panel, as the viewer reads it. */
async function expectLeagueDetails(page: Page, details: [string, string | RegExp][]) {
  for (const [label, value] of details) {
    const text = typeof value === 'string' ? `${label}: ${value}` : new RegExp(`${label}:\\s*${value.source}`);
    await expect(leagueDetails(page)).toContainText(text, VISIBLE_TEXT);
  }
}

async function expectSettingUpView(page: Page, draft: Draft, commissioner: LoggedInUser) {
  await expect(leagueDetailsHeading(page, draft)).toBeVisible();
  await expectLeagueDetails(page, [
    ['League Name', draft.name],
    ['Commissioner', commissioner.name],
    ['Fantasy Sport', 'Football (NFL)'],
    ['Draft Status', 'Setting Up'],
    ['Draft Style', 'Serpentine Draft'],
    ['Positions', 'Off'],
    ['# of Rounds', '2'],
  ]);
  for (const [index, manager] of draft.managers.entries()) {
    await expect(draftOrder(page)).toContainText(`${ordinal(index + 1)} ${manager.name}`, VISIBLE_TEXT);
  }
}

// The tests have no Draft with more than three Managers
function ordinal(position: number): string {
  return `${position}${['st', 'nd', 'rd'][position - 1]}`;
}

/** "Last 5 Picks" holds this one Pick and no other. */
async function expectOnlyLastPick(page: Page, pick: Pick<PickEntry, 'firstName' | 'lastName'>) {
  await expect(pickRows(lastPicks(page))).toHaveText([new RegExp(`${pick.firstName} ${pick.lastName}`)], VISIBLE_TEXT);
}

async function expectOnTheClockFirst(page: Page) {
  await expect(nextPick(page)).toContainText('On the clock', VISIBLE_TEXT);
}

/** Enters the next `count` Picks of an In Progress Draft. Returns the Selected Picks in Pick order. */
async function addPicks(setup: ApiSetup, commissioner: LoggedInUser, draft: Draft, count: number) {
  const picks: SelectedPick[] = [];
  for (let pick = 1; pick <= count; pick++) {
    picks.push(await setup.addPick(commissioner, draft));
  }

  return picks;
}

/** A Complete Draft of two Rounds and two Managers. Returns its Selected Picks in Pick order. */
async function completeDraft(setup: ApiSetup, commissioner: LoggedInUser) {
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 2 });
  const picks = await addPicks(setup, commissioner, draft, 4);

  return { draft, picks };
}

async function expectRoundPicks(page: Page, picks: SelectedPick[], round: number) {
  await expect(roundPicksHeading(page, round)).toBeVisible();
  await expect(pickRows(roundPicks(page))).toHaveText(
    picks
      .filter((pick) => pick.round === round)
      .map((pick) => new RegExp(`Round\\s*${round}\\s[\\s\\S]*${pick.firstName} ${pick.lastName}`)),
    VISIBLE_TEXT,
  );
}

/** The view of INDEX-06: the donation message, the end times, and the Round Picks with their pager. */
async function expectCompleteView(page: Page, picks: SelectedPick[]) {
  await expect(donationMessage(page)).toBeVisible();
  await expectLeagueDetails(page, [
    ['Draft Status', 'Completed'],
    ['Started', DATE_TIME],
    ['Ended', DATE_TIME],
  ]);
  await expectRoundPicks(page, picks, 1);

  await roundPicks(page).getByRole('link', { name: /Next/ }).click();

  await expectRoundPicks(page, picks, 2);
}

test('INDEX-01 a Setting Up Draft shows the League Details and the Draft Order', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { managers: [unique('Ann'), unique('Bob')] });

  await openDraftScreen(page, draft);

  await expectSettingUpView(page, draft, commissioner);
  await expect(commishActions(page)).toHaveCount(0);
});

test('INDEX-02 a Setting Up Draft with no Managers says that none are added', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner);

  await openDraftScreen(page, draft);

  await expect(leagueDetailsHeading(page, draft)).toBeVisible();
  await expect(draftOrder(page)).toContainText(`${commissioner.name} has not added any managers yet.`, VISIBLE_TEXT);
});

test('INDEX-03 a Draft with Depth Charts lists each position with its slots', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, {
    depthChartPositions: [
      { position: 'QB', slots: 1 },
      { position: 'RB', slots: 2 },
    ],
  });

  await openDraftScreen(page, draft);

  await expectLeagueDetails(page, [['Positions', /1×\s*QB\s*2×\s*RB/]]);
});

test('INDEX-04 an In Progress Draft with no Selected Pick shows the next five Picks and no last Picks', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 3 });

  await openDraftScreen(page, draft);

  await expect(leagueDetailsHeading(page, draft)).toBeVisible();
  await expect(pickRows(upcomingPicks(page))).toHaveText([1, 2, 3, 4, 5].map(pickNumber), VISIBLE_TEXT);
  await expectOnTheClockFirst(page);
  await expect(lastPicks(page)).toContainText('No picks have been made yet...', VISIBLE_TEXT);
});

test('INDEX-05 Last 5 Picks shows the five newest Selected Picks', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 4 });
  const picks = await addPicks(setup, commissioner, draft, 6);

  await openDraftScreen(page, draft);

  const newestFirst = picks.slice(1).reverse();
  await expect(pickRows(lastPicks(page))).toHaveCount(5);
  for (const [index, pick] of newestFirst.entries()) {
    const manager = draft.managers.find((candidate) => candidate.id === pick.managerId)!;
    const row = pickRows(lastPicks(page)).nth(index);
    await expect(row).toContainText(pickNumber(pick.number), VISIBLE_TEXT);
    await expect(row).toContainText(`Round ${pick.round}`, VISIBLE_TEXT);
    await expect(row).toContainText(`${pick.firstName} ${pick.lastName}`, VISIBLE_TEXT);
    await expect(row).toContainText('Wide Receiver – Green Bay Packers', VISIBLE_TEXT);
    await expect(row).toContainText(manager.name, VISIBLE_TEXT);
  }
});

test('INDEX-06 a Complete Draft shows the donation message, the end times, and the Round Picks', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDraft(setup, commissioner);

  await openDraftScreen(page, draft);

  await expectCompleteView(page, picks);
});

test('INDEX-07 a hidden donation message stays hidden for 14 days', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'Complete', rounds: 1 });
  await openDraftScreenWithClock(page, draft);

  await donationMessage(page).getByTestId('hide-donation-message').click();

  await expect(donationMessage(page)).toBeHidden();
  await page.reload();
  await expect(roundPicksHeading(page, 1)).toBeVisible();
  await expect(donationMessage(page)).toBeHidden();

  await page.clock.setSystemTime(Date.now() + (DONATION_HIDE_DAYS + 1) * 24 * 60 * 60 * 1000);
  await page.reload();

  await expect(donationMessage(page)).toBeVisible();
});

test('INDEX-08 the Commissioner of a Setting Up Draft gets the Commish Actions and the Manager editor', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { managers: [unique('Ann'), unique('Bob')] });
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expect(commishActionEntries(page)).toHaveText(['Start!', 'Timers', 'Edit', 'Delete'], VISIBLE_TEXT);
  await expect(draftOrder(page).getByRole('button', { name: 'Randomize Order' })).toBeVisible();
  await expect(draftOrder(page).getByRole('button', { name: 'Add Managers' })).toBeVisible();

  // The Edit icon link has no accessible name, and it is the only link of the panel
  await leagueDetails(page).getByRole('link').click();

  await expect(page).toHaveURL(new RegExp(`/commish/draft/${draft.id}/edit$`));
});

test('INDEX-09 the Commissioner of an In Progress Draft gets six Commish Actions that open their routes', async ({
  page,
  context,
  setup,
}) => {
  const { draft, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);

  await expect(commishActionEntries(page)).toHaveText(
    ['Enter Pick', 'Change Pick', 'Enter Trade', 'Reset', 'Timers', 'Delete'],
    VISIBLE_TEXT,
  );
  await expect(leagueDetails(page)).toContainText(
    "Can't edit active draft – Reset draft if you need to change something",
    VISIBLE_TEXT,
  );

  await expectMenuLinksOpenRoutes(
    page,
    {
      linkByName: (name) => commishActions(page).getByRole('link', { name }),
      reopen: async () => {
        await openDraftScreen(page, draft);
      },
    },
    [
      { link: 'Enter Pick', path: 'picks/add' },
      { link: 'Change Pick', path: 'picks/edit' },
      { link: 'Enter Trade', path: 'trades/add' },
      { link: 'Timers', path: 'timers' },
    ].map(({ link, path }) => ({ link, url: new RegExp(`/commish/draft/${draft.id}/${path}$`) })),
  );
  await expect(page).toHaveURL(new RegExp(`${draftRoute}$`));
});

test('INDEX-10 the Commissioner of a Complete Draft gets the view of INDEX-06 and no Commish Actions', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const { draft, picks } = await completeDraft(setup, commissioner);
  await setup.logInBrowser(context, commissioner);

  await openDraftScreen(page, draft);

  await expectCompleteView(page, picks);
  await expect(commishActions(page)).toHaveCount(0);
});

test('INDEX-11 API errors show the toasts and the error text of each panel', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-24');
  const commissioner = await setup.registerCommissioner();
  const settingUp = await setup.createDraft(commissioner, { managers: [unique('Ann')] });
  const complete = await setup.createDraft(commissioner, { status: 'Complete', rounds: 1 });
  const inProgress = await setup.createDraft(commissioner, { status: 'In Progress' });
  await failApiRequest(page, { path: /^draft\/\d+\/managers$/ });
  await failApiRequest(page, { path: /^draft\/\d+\/picks\/last$/ });
  await failApiRequest(page, { path: /^draft\/\d+\/round\/\d+\/picks\/selected$/ });

  await openDraftScreen(page, settingUp);
  await expectToast(page, { message: 'Unable to load managers' });

  await openDraftScreen(page, complete);
  await expectToast(page, { message: 'Unable to load picks' });
  // Soft, so that both W-24 checks run on AngularJS
  await expect.soft(roundPicks(page)).toContainText('Unable to load round 1 picks', VISIBLE_TEXT);

  await openDraftScreen(page, inProgress);
  await expect.soft(lastPicks(page)).toContainText('Unable to load last 5 picks', VISIBLE_TEXT);
});

test('INDEX-12 a second viewer sees a new Pick in Last 5 Picks after the next poll', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 4 });
  await openDraftScreenWithClock(page, draft);
  await expect(nextPick(page)).toContainText(pickNumber(1), VISIBLE_TEXT);

  const pick = await setup.addPick(commissioner, draft);
  await page.clock.runFor(DRAFT_POLL_MS);

  await expectOnlyLastPick(page, pick);
  await expect(pickRows(upcomingPicks(page))).toHaveText([2, 3, 4, 5, 6].map(pickNumber), VISIBLE_TEXT);
});

test('INDEX-13 a second viewer sees an edited Pick after the next poll', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const pick = await setup.addPick(commissioner, draft);
  await openDraftScreenWithClock(page, draft);
  await expectOnlyLastPick(page, pick);

  const edited = { ...pick, firstName: 'Edited', lastName: unique('Name') };
  await setup.editPick(commissioner, draft, edited);
  await page.clock.runFor(DRAFT_POLL_MS);

  await expectOnlyLastPick(page, edited);
});

test('INDEX-14 a second viewer sees the new Manager of a Traded Pick after the next poll', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  const [first, second] = draft.managers;
  const onTheClock = await setup.currentPick(commissioner, draft);
  await openDraftScreenWithClock(page, draft);
  await expect(nextPick(page)).toContainText(first.name, VISIBLE_TEXT);

  await setup.addTrade(commissioner, draft, {
    manager1Id: first.id,
    manager2Id: second.id,
    pickIds: [onTheClock.id],
  });
  await page.clock.runFor(DRAFT_POLL_MS);

  await expect(nextPick(page)).toContainText(second.name, VISIBLE_TEXT);
});

test('INDEX-15 a second viewer sees the Complete view after the last Pick, and polling stops', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 2 });
  const picks = await addPicks(setup, commissioner, draft, 3);
  await openDraftScreenWithClock(page, draft);
  await expect(pickRows(upcomingPicks(page))).toHaveCount(1);

  picks.push(await setup.addPick(commissioner, draft));
  await page.clock.runFor(DRAFT_POLL_MS);

  await expectCompleteView(page, picks);
  await expectNoDraftPolls(page, draft);
});

test('INDEX-16 a second viewer sees the Setting Up view after a reset', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-39');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await openDraftScreenWithClock(page, draft);
  await expectOnTheClockFirst(page);

  await setup.setDraftStatus(commissioner, draft, 'Setting Up');
  await page.clock.runFor(DRAFT_POLL_MS);

  await expectSettingUpView(page, draft, commissioner);
});

test('INDEX-17 a second viewer sees a started Draft only after a reload', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { managers: [unique('Ann'), unique('Bob')] });
  await openDraftScreenWithClock(page, draft);
  await expect(draftOrder(page)).toBeVisible();

  await setup.setDraftStatus(commissioner, draft, 'In Progress');
  await expectNoDraftPolls(page, draft);

  await expectLeagueDetails(page, [['Draft Status', 'Setting Up']]);
  await expect(draftOrder(page)).toBeVisible();
  await expect(upcomingPicks(page)).toBeHidden();

  await page.reload();

  await expectLeagueDetails(page, [['Draft Status', 'In Progress']]);
  await expectOnTheClockFirst(page);
});
