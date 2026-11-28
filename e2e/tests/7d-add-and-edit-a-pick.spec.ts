import { BrowserContext, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser, PickEntry, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { boardPicks, boardRoute } from '../helpers/draft-board';
import {
  commishActions,
  draftIndexRoute,
  DraftRoute,
  expectDraftStatusRedirect,
  openDraftScreen,
  pickRows,
  visiblePanel,
} from '../helpers/draft-screen';
import { cancelButton, workingModal } from '../helpers/form';
import { failApiRequest, holdApiResponses, recordApiRequests, waitForApiResponse } from '../helpers/intercept';
import { expectLoginRoute } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { fullName, VISIBLE_TEXT } from '../helpers/text';
import { expectExpiredSessionLogout, expectSingleToast, expectToast, SERVER_ERROR_TOAST } from '../helpers/toast';

// Route group 7D of docs/flow-inventory.md. Each test name starts with the row ID.

const addRoute: DraftRoute = (draft) => `/commish/draft/${draft.id}/picks/add`;
const editRoute: DraftRoute = (draft) => `/commish/draft/${draft.id}/picks/edit`;

const currentPickPath = (draft: Pick<Draft, 'id'>) => `commish/draft/${draft.id}/pick/current`;
const duplicateCheckPath = (draft: Pick<Draft, 'id'>) => `commish/draft/${draft.id}/picks/alreadyDrafted`;
const roundPicksPath = (draft: Pick<Draft, 'id'>, round: number) => `draft/${draft.id}/round/${round}/picks/selected`;
const SAVE_PICK_PATH = /^commish\/draft\/\d+\/pick\/\d+$/;
const PRO_PLAYER_SEARCH_PATH = 'commish/proplayers/search';

const RESET_TOAST = 'Pick was reset - go ahead and enter another player.';

// A Pro Player of the seeded NFL list, and the labels that the Pick form shows for its team and position
const PRO_PLAYER = { firstName: 'Davante', lastName: 'Adams', team: 'GNB', position: 'WR' } satisfies PickEntry;
const PRO_PLAYER_NAME = fullName(PRO_PLAYER);
const TEAM_LABEL = 'Green Bay Packers';
const POSITION_LABEL = 'Wide Receiver';

const enterHeading = (page: Page) => page.getByRole('heading', { name: 'Enter Picks' });
const searchBox = (page: Page) => page.getByRole('textbox', { name: 'Search Players' });
const manualEntrySwitch = (page: Page) => page.getByTestId('manual-entry-switch');
const firstNameField = (page: Page) => page.getByRole('textbox', { name: 'First Name' });
const lastNameField = (page: Page) => page.getByRole('textbox', { name: 'Last Name' });
const teamField = (page: Page) => page.getByRole('combobox', { name: 'Team' });
const positionField = (page: Page) => page.getByRole('combobox', { name: 'Position' });
const entryDisplay = (page: Page) => page.getByTestId('pick-entry-display');
const suggestions = (page: Page) => page.getByTestId('pro-player-suggestion');
const enterButton = (page: Page) => page.getByRole('button', { name: 'Enter pick' });
const updateButton = (page: Page) => page.getByRole('button', { name: 'Update pick' });
const undoButton = (page: Page) => page.getByRole('button', { name: 'Undo' });
const duplicateDialog = (page: Page) => page.getByRole('dialog').filter({ hasText: 'Player was already picked' });
const sureButton = (page: Page) => page.getByRole('button', { name: /I'm sure/ });
const mistakeButton = (page: Page) => page.getByRole('button', { name: /Whoops, its a mistake/ });
const upcomingPicks = (page: Page) => visiblePanel(page, 'upcoming-picks-panel');
const previousPicks = (page: Page) => visiblePanel(page, 'previous-picks-panel');

const editHeading = (page: Page, round: number) =>
  page.getByRole('heading', { name: `Edit Pick – Round ${round} Picks` });
const selectedPickRows = (page: Page) => page.getByTestId('selected-pick-row').filter({ visible: true });
const editButtons = (page: Page) => page.getByTestId('edit-pick-button').filter({ visible: true });
const pagerLink = (page: Page, name: 'Previous' | 'Next') => page.getByRole('link', { name: new RegExp(name) });
const emptyRound = (page: Page, round: number) => page.getByText(`Round ${round} currently doesn't have any Picks.`);

/** The toggle switch exposes no state to role or label, so the fields that it shows stand for its state. */
async function expectManualEntry(page: Page, state: 'on' | 'off') {
  const [shown, hidden] =
    state === 'on' ? [firstNameField(page), searchBox(page)] : [searchBox(page), firstNameField(page)];

  await expect(shown).toBeVisible();
  await expect(hidden).toBeHidden();
}

async function openAddPick(page: Page, draft: Draft) {
  const current = waitForApiResponse(page, { method: 'GET', path: currentPickPath(draft) });

  await page.goto(addRoute(draft));
  await current;
  await expect(enterHeading(page)).toBeVisible();
  await expect(searchBox(page)).toBeVisible();
}

async function openAddPickFromIndex(page: Page, draft: Draft) {
  await openDraftScreen(page, draft);
  await commishActions(page).getByRole('link', { name: 'Enter Pick' }).click();
  await expect(page).toHaveURL(addRoute(draft));
  await expect(enterHeading(page)).toBeVisible();
}

async function openEditPick(page: Page, draft: Draft, round: number) {
  const picks = waitForApiResponse(page, { method: 'GET', path: roundPicksPath(draft, round) });

  await page.goto(editRoute(draft));
  await picks;
  await expect(editHeading(page, round)).toBeVisible();
}

async function openEditPickFromIndex(page: Page, draft: Draft, round: number) {
  await openDraftScreen(page, draft);
  await commishActions(page).getByRole('link', { name: 'Change Pick' }).click();
  await expect(page).toHaveURL(editRoute(draft));
  await expect(editHeading(page, round)).toBeVisible();
}

/** Logs in a new Commissioner with an In Progress Draft of two Managers, Ann and Bob. */
async function newInProgressDraft(
  context: BrowserContext,
  setup: ApiSetup,
  options: { rounds?: number } = {},
): Promise<{ commissioner: LoggedInUser; draft: Draft; managers: string[] }> {
  const managers = [unique('Ann'), unique('Bob')];
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', managers, ...options });

  return { commissioner, draft, managers };
}

/** Logs in a new Admin. Another Commissioner owns the In Progress Draft of two Managers. */
async function newDraftOfAnotherUserForAdmin(
  context: BrowserContext,
  setup: ApiSetup,
): Promise<{ draft: Draft; managers: string[] }> {
  const managers = [unique('Ann'), unique('Bob')];
  const owner = await setup.registerCommissioner();
  const draft = await setup.createDraft(owner, { status: 'In Progress', managers });
  await setup.logInNewAdmin(context);

  return { draft, managers };
}

/** Searches for a Pro Player and chooses the suggestion that holds `name`. */
async function chooseProPlayer(page: Page, term: string, name: string) {
  await searchBox(page).fill(term);
  await suggestions(page).filter({ hasText: name }).click();
  await expect(entryDisplay(page)).toContainText(name, VISIBLE_TEXT);
}

/** Chooses the Pro Player who is already a Selected Pick: the state of ADDPICK-03. */
async function openDuplicateDialog(page: Page) {
  await searchBox(page).fill(PRO_PLAYER.firstName);
  await suggestions(page).filter({ hasText: PRO_PLAYER_NAME }).click();
  await expect(duplicateDialog(page)).toBeVisible();
}

async function turnOnManualEntry(page: Page) {
  await manualEntrySwitch(page).click();
  await expectManualEntry(page, 'on');
}

async function fillManualEntry(page: Page, pick: Pick<PickEntry, 'firstName' | 'lastName'>) {
  await turnOnManualEntry(page);
  await firstNameField(page).fill(pick.firstName);
  await lastNameField(page).fill(pick.lastName);
  await teamField(page).selectOption({ label: TEAM_LABEL });
  await positionField(page).selectOption({ label: POSITION_LABEL });
}

/** The Pick form shows the Pick as an Unselected Pick: the Manager, and no player. */
async function expectUnselectedEntry(page: Page, manager: string, absentPlayerName: string) {
  await expect(entryDisplay(page)).toContainText(manager, VISIBLE_TEXT);
  await expect(entryDisplay(page)).not.toContainText(absentPlayerName, VISIBLE_TEXT);
}

async function expectSavedToast(page: Page, name: string) {
  await expectToast(page, { message: `${name} drafted` });
}

test('ADDPICK-01 the panel Enter Picks shows the search box, the current Pick, and the two Pick lists', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);

  await openAddPickFromIndex(page, draft);

  await expect(enterHeading(page)).toBeVisible();
  await expect(searchBox(page)).toBeFocused();
  await expect(manualEntrySwitch(page)).toBeVisible();
  await expectManualEntry(page, 'off');
  await expect(entryDisplay(page)).toContainText(managers[0], VISIBLE_TEXT);
  await expect(enterButton(page)).toBeDisabled();
  await expect(undoButton(page)).toBeVisible();
  await expect(upcomingPicks(page).getByRole('heading', { name: 'Upcoming Picks' })).toBeVisible();
  await expect(previousPicks(page).getByRole('heading', { name: 'Previous Picks' })).toBeVisible();
  await expect(previousPicks(page)).toContainText('No picks have been made yet.', VISIBLE_TEXT);
});

// ADDPICK-15 is ADDPICK-02 for an Admin in the Draft of a different user
for (const { row, viewer, newDraft } of [
  { row: 'ADDPICK-02', viewer: 'the Commissioner', newDraft: newInProgressDraft },
  { row: 'ADDPICK-15', viewer: 'an Admin', newDraft: newDraftOfAnotherUserForAdmin },
]) {
  test(`${row} ${viewer} chooses a suggestion, and Enter pick saves the Pick and loads the next Pick`, async ({
    page,
    context,
    setup,
  }) => {
    const { draft, managers } = await newDraft(context, setup);
    await openAddPick(page, draft);

    await searchBox(page).fill('an');
    await expect(suggestions(page)).toHaveCount(10);

    await searchBox(page).fill(PRO_PLAYER.firstName);
    await expect(suggestions(page)).toHaveCount(1);
    await expect(suggestions(page)).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
    await expect(suggestions(page)).toContainText(POSITION_LABEL, VISIBLE_TEXT);
    await expect(suggestions(page)).toContainText(TEAM_LABEL, VISIBLE_TEXT);

    await suggestions(page).click();

    await expect(entryDisplay(page)).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
    await expect(entryDisplay(page)).toContainText(managers[0], VISIBLE_TEXT);
    await expect(enterButton(page)).toBeEnabled();

    const release = await holdApiResponses(page, { method: 'POST', path: SAVE_PICK_PATH });
    await enterButton(page).click();

    await expect(workingModal(page)).toBeVisible();
    release();
    await expect(workingModal(page)).toBeHidden();
    await expectSavedToast(page, PRO_PLAYER_NAME);
    await expect(entryDisplay(page)).toContainText(managers[1], VISIBLE_TEXT);
    await expect(entryDisplay(page)).not.toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
    await expect(pickRows(previousPicks(page)).first()).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
  });

  test(`${row} the search box has the focus again after ${viewer} saves a Pick`, async ({ page, context, setup }) => {
    knownFailureOnAngularJs('W-46');
    const { draft, managers } = await newDraft(context, setup);
    await openAddPick(page, draft);
    await chooseProPlayer(page, PRO_PLAYER.firstName, PRO_PLAYER_NAME);
    // W-46 on AngularJS is a race: the box takes the focus after the next Pick loads, then the modal takes it at the
    // end of its 0.3 s open transition. On a loaded runner the next Pick can lose that race and the box keeps the
    // focus. A slower transition keeps the order that a fast API gives. The selector matches nothing in the new client.
    await page.addStyleTag({ content: '.modal.fade .modal-dialog { transition-duration: 1.5s !important; }' });

    await enterButton(page).click();

    await expectSavedToast(page, PRO_PLAYER_NAME);
    await expect(entryDisplay(page)).toContainText(managers[1], VISIBLE_TEXT);
    // The box can have the focus for a moment before the modal takes it, so the focus counts only once the modal is gone
    await expect(workingModal(page)).toBeHidden();
    await expect(searchBox(page)).toBeFocused();
  });
}

test('ADDPICK-03 choosing a Pro Player who is a Selected Pick shows the dialog with the matching Pick', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft } = await newInProgressDraft(context, setup);
  await setup.addPick(commissioner, draft, PRO_PLAYER);
  await openAddPick(page, draft);

  await openDuplicateDialog(page);

  await expect(pickRows(duplicateDialog(page))).toHaveCount(1);
  await expect(duplicateDialog(page)).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
});

test('ADDPICK-04 "I\'m sure" closes the dialog, keeps the name, and Enter pick saves the Pick', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft } = await newInProgressDraft(context, setup);
  await setup.addPick(commissioner, draft, PRO_PLAYER);
  await openAddPick(page, draft);
  await openDuplicateDialog(page);

  await sureButton(page).click();

  await expect(duplicateDialog(page)).toBeHidden();
  await expect(entryDisplay(page)).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);

  await enterButton(page).click();

  await expectSavedToast(page, PRO_PLAYER_NAME);
  await expect(pickRows(previousPicks(page)).first()).toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);
});

test('ADDPICK-05 "Whoops, its a mistake" closes the dialog and resets the Pick with a toast', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft, managers } = await newInProgressDraft(context, setup);
  await setup.addPick(commissioner, draft, PRO_PLAYER);
  await openAddPick(page, draft);
  await openDuplicateDialog(page);

  await mistakeButton(page).click();

  await expect(duplicateDialog(page)).toBeHidden();
  await expectUnselectedEntry(page, managers[1], PRO_PLAYER_NAME);
  await expectToast(page, { message: RESET_TOAST });
});

test('ADDPICK-06 Manual Entry shows four fields, enables Enter pick with all four, and saves the Pick', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);
  const pick = { firstName: unique('Zed'), lastName: unique('Manual') };
  await openAddPick(page, draft);

  await turnOnManualEntry(page);

  await expect(lastNameField(page)).toBeVisible();
  await expect(teamField(page)).toBeVisible();
  await expect(positionField(page)).toBeVisible();
  await expect(enterButton(page)).toBeDisabled();

  await firstNameField(page).fill(pick.firstName);
  await lastNameField(page).fill(pick.lastName);
  await expect(enterButton(page)).toBeDisabled();
  await teamField(page).selectOption({ label: TEAM_LABEL });
  await expect(enterButton(page)).toBeDisabled();
  await positionField(page).selectOption({ label: POSITION_LABEL });
  await expect(enterButton(page)).toBeEnabled();

  await enterButton(page).click();

  await expectSavedToast(page, fullName(pick));
  await expect(entryDisplay(page)).toContainText(managers[1], VISIBLE_TEXT);
  await expect(pickRows(previousPicks(page)).first()).toContainText(fullName(pick), VISIBLE_TEXT);
});

test('ADDPICK-06 the Pick display follows the Manual Entry fields', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-45');
  const { draft } = await newInProgressDraft(context, setup);
  const pick = { firstName: unique('Zed'), lastName: unique('Manual') };
  await openAddPick(page, draft);
  await turnOnManualEntry(page);

  await firstNameField(page).fill(pick.firstName);
  await lastNameField(page).fill(pick.lastName);

  await expect(entryDisplay(page)).toContainText(fullName(pick), VISIBLE_TEXT);
});

test('ADDPICK-06 Manual Entry is off for the next Pick after a save', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-44');
  const { draft, managers } = await newInProgressDraft(context, setup);
  const pick = { firstName: unique('Zed'), lastName: unique('Manual') };
  await openAddPick(page, draft);
  await fillManualEntry(page, pick);

  await enterButton(page).click();

  await expectSavedToast(page, fullName(pick));
  await expect(entryDisplay(page)).toContainText(managers[1], VISIBLE_TEXT);
  await expectManualEntry(page, 'off');
});

test('ADDPICK-07 Manual Entry with the name of a Selected Pick shows the dialog before the save', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-44');
  const { commissioner, draft, managers } = await newInProgressDraft(context, setup, { rounds: 3 });
  const name = { firstName: unique('Dup'), lastName: unique('Entry') };
  await setup.addPick(commissioner, draft, name);
  await openAddPick(page, draft);
  const saves = recordApiRequests(page, { method: 'POST', path: SAVE_PICK_PATH });
  await fillManualEntry(page, name);

  await enterButton(page).click();

  await expect(duplicateDialog(page)).toBeVisible();
  expect(saves).toEqual([]);

  await sureButton(page).click();

  await expectSavedToast(page, fullName(name));
  await expect(pickRows(previousPicks(page)).first()).toContainText(fullName(name), VISIBLE_TEXT);

  await fillManualEntry(page, name);
  await enterButton(page).click();
  await expect(duplicateDialog(page)).toBeVisible();

  await mistakeButton(page).click();

  await expect(duplicateDialog(page)).toBeHidden();
  await expectToast(page, { message: RESET_TOAST });
  // The third Pick opens Round 2, which a serpentine Draft gives to the second Manager
  await expectUnselectedEntry(page, managers[1], fullName(name));
});

test('ADDPICK-08 Undo makes the Pick an Unselected Pick again and turns Manual Entry off', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);
  await openAddPick(page, draft);
  await chooseProPlayer(page, PRO_PLAYER.firstName, PRO_PLAYER_NAME);
  await turnOnManualEntry(page);

  await undoButton(page).click();

  await expectUnselectedEntry(page, managers[0], PRO_PLAYER_NAME);
  await expectManualEntry(page, 'off');
  await expect(enterButton(page)).toBeDisabled();
});

for (const { row, status, message } of [
  {
    row: 'ADDPICK-09',
    status: 'Setting Up',
    message: 'Unable to add picks for draft: draft has not been started yet.',
  },
  { row: 'ADDPICK-10', status: 'Complete', message: 'Unable to add picks for draft: draft is already completed' },
] as const) {
  test(`${row} a ${status} Draft shows one warning toast and goes to the previous route`, async ({
    page,
    context,
    setup,
  }) => {
    knownFailureOnAngularJs('W-06, W-16');

    await expectDraftStatusRedirect(page, setup, { route: addRoute, status, message, loggedInContext: context });

    await expectSingleToast(page, message);
    await expect(page.getByText('Unable to get current pick')).toBeHidden();
  });
}

test('ADDPICK-11 entering the last Pick shows the working modal, then the Complete view and a toast', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft } = await newInProgressDraft(context, setup, { rounds: 1 });
  await setup.addPick(commissioner, draft);
  const name = { firstName: unique('Last'), lastName: unique('Pick') };
  await page.clock.install();
  await openAddPick(page, draft);
  await fillManualEntry(page, name);

  await enterButton(page).click();

  await expectSavedToast(page, fullName(name));
  await expect(workingModal(page)).toBeVisible();
  await expect(page).toHaveURL(addRoute(draft));

  await page.clock.runFor(1_500);

  await expect(page).toHaveURL(draftIndexRoute(draft));
  await expect(workingModal(page)).toBeHidden();
  await expect(visiblePanel(page.locator('phpd-draft-completed'), 'draft-details-panel')).toContainText(
    'Completed',
    VISIBLE_TEXT,
  );
  await expectToast(page, { title: "That's a Wrap!", message: 'Congrats! Your draft has been completed.' });
});

test('ADDPICK-12 an API error shows a toast for the current Pick, the search, and the save', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await newInProgressDraft(context, setup);

  await failApiRequest(page, { method: 'GET', path: currentPickPath(draft) });
  await page.goto(addRoute(draft));
  await expectToast(page, { message: 'Unable to get current pick' });

  const searchPage = await context.newPage();
  await failApiRequest(searchPage, { method: 'GET', path: PRO_PLAYER_SEARCH_PATH });
  await openAddPick(searchPage, draft);
  await searchBox(searchPage).fill(PRO_PLAYER.firstName);
  await expectToast(searchPage, { message: 'Unable to search pro players' });

  const savePage = await context.newPage();
  await failApiRequest(savePage, { method: 'POST', path: SAVE_PICK_PATH });
  await openAddPick(savePage, draft);
  await fillManualEntry(savePage, { firstName: unique('Save'), lastName: unique('Fails') });
  await enterButton(savePage).click();
  await expectToast(savePage, { title: 'Unable to enter pick', message: SERVER_ERROR_TOAST });
});

test('ADDPICK-12 an API error of the duplicate check shows a toast and keeps Enter pick enabled', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-19, W-44');
  const { draft } = await newInProgressDraft(context, setup);
  await failApiRequest(page, { method: 'GET', path: duplicateCheckPath(draft) });
  await openAddPick(page, draft);
  await fillManualEntry(page, { firstName: unique('Check'), lastName: unique('Fails') });

  await enterButton(page).click();

  await expectToast(page, { message: 'Unable to search for player already drafted' });
  await expect(enterButton(page)).toBeEnabled();
});

/** A Draft in Round 2 with Selected Picks in Rounds 1 and 2. */
async function draftInRoundTwo(context: BrowserContext, setup: ApiSetup) {
  const state = await newInProgressDraft(context, setup, { rounds: 3 });
  const picks = await setup.addPicks(state.commissioner, state.draft, 3);

  return { ...state, picks };
}

test('EDITPICK-01 the panel Edit Pick shows the Selected Picks of the current Round with an edit button each', async ({
  page,
  context,
  setup,
}) => {
  const { draft, picks } = await draftInRoundTwo(context, setup);

  await openEditPickFromIndex(page, draft, 2);

  await expect(pagerLink(page, 'Previous')).toBeVisible();
  await expect(pagerLink(page, 'Next')).toBeVisible();
  await expect(selectedPickRows(page)).toHaveCount(1);
  await expect(selectedPickRows(page).first()).toContainText(fullName(picks[2]), VISIBLE_TEXT);
  await expect(editButtons(page)).toHaveCount(1);
  await expect(page.getByText(fullName(picks[0]))).toBeHidden();
});

test('EDITPICK-02 the pager changes the Round, and a Round with no Selected Pick shows a text', async ({
  page,
  context,
  setup,
}) => {
  const { draft, picks } = await draftInRoundTwo(context, setup);
  await openEditPick(page, draft, 2);

  await pagerLink(page, 'Previous').click();

  await expect(editHeading(page, 1)).toBeVisible();
  await expect(selectedPickRows(page)).toHaveCount(2);
  await expect(selectedPickRows(page).nth(0)).toContainText(fullName(picks[0]), VISIBLE_TEXT);
  await expect(selectedPickRows(page).nth(1)).toContainText(fullName(picks[1]), VISIBLE_TEXT);

  await pagerLink(page, 'Next').click();
  await pagerLink(page, 'Next').click();

  await expect(editHeading(page, 3)).toBeVisible();
  await expect(emptyRound(page, 3)).toBeVisible();
  await expect(selectedPickRows(page)).toHaveCount(0);
});

test('EDITPICK-03 choosing another Pro Player and selecting Update pick saves it and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const { draft } = await draftInRoundTwo(context, setup);
  await openEditPickFromIndex(page, draft, 2);

  await editButtons(page).first().click();

  await expect(updateButton(page)).toBeVisible();
  await expect(enterButton(page)).toBeHidden();

  await chooseProPlayer(page, PRO_PLAYER.firstName, PRO_PLAYER_NAME);
  await updateButton(page).click();

  await expectToast(page, { message: `${PRO_PLAYER_NAME} updated` });
  await expect(page).toHaveURL(draftIndexRoute(draft));

  await page.goto(boardRoute(draft));
  await expect(boardPicks(page).filter({ hasText: PRO_PLAYER_NAME })).toBeVisible();
});

test('EDITPICK-04 Manual Entry holds the values of the Pick, and a changed name is checked, then saved', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06, W-44');
  const { draft, picks } = await draftInRoundTwo(context, setup);
  const pick = picks[2];
  await openEditPickFromIndex(page, draft, 2);
  await editButtons(page).first().click();

  await turnOnManualEntry(page);

  await expect(firstNameField(page)).toHaveValue(pick.firstName);
  await expect(lastNameField(page)).toHaveValue(pick.lastName);
  await expect(teamField(page).getByRole('option', { selected: true })).toHaveText(TEAM_LABEL);
  await expect(positionField(page).getByRole('option', { selected: true })).toHaveText(POSITION_LABEL);

  const nickname = unique('Nick');
  const checks = recordApiRequests(page, { method: 'GET', path: duplicateCheckPath(draft) });
  await firstNameField(page).fill(nickname);
  await updateButton(page).click();

  await expectToast(page, { message: `${nickname} ${pick.lastName} updated` });
  expect(checks).toHaveLength(1);
  await expect(duplicateDialog(page)).toBeHidden();
  await expect(page).toHaveURL(draftIndexRoute(draft));
});

test('EDITPICK-05 a Setting Up or Complete Draft shows a warning toast and goes to the Draft index', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-15');
  const commissioner = await setup.logInNewCommissioner(context);
  const settingUp = await setup.createDraft(commissioner);
  const complete = await setup.createDraft(commissioner, { status: 'Complete' });

  await page.goto(editRoute(settingUp));

  await expectToast(page, { message: 'Unable to edit picks for draft: draft has not been started yet.' });
  await expect(page).toHaveURL(draftIndexRoute(settingUp));

  const completePage = await context.newPage();
  await completePage.goto(editRoute(complete));

  await expectToast(completePage, { message: 'Unable to edit picks for draft: draft is already completed' });
  await expect(completePage).toHaveURL(draftIndexRoute(complete));
});

test('EDITPICK-06 Undo shows the saved values again, and Cancel returns to the list of Picks', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-31');
  const { draft, picks } = await draftInRoundTwo(context, setup);
  await openEditPick(page, draft, 2);
  await editButtons(page).first().click();
  await chooseProPlayer(page, PRO_PLAYER.firstName, PRO_PLAYER_NAME);

  await undoButton(page).click();

  await expect(entryDisplay(page)).toContainText(fullName(picks[2]), VISIBLE_TEXT);
  await expect(entryDisplay(page)).not.toContainText(PRO_PLAYER_NAME, VISIBLE_TEXT);

  await expect(cancelButton(page)).toBeVisible();
  await cancelButton(page).click();

  await expect(editHeading(page, 2)).toBeVisible();
  await expect(selectedPickRows(page)).toHaveCount(1);
});

test('EDITPICK-07 an expired session logs the viewer out with the one shared toast', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-33');
  const { commissioner, draft } = await draftInRoundTwo(context, setup);
  await openEditPick(page, draft, 2);
  await editButtons(page).first().click();
  await chooseProPlayer(page, PRO_PLAYER.firstName, PRO_PLAYER_NAME);
  await commissioner.api.post('logout');

  await updateButton(page).click();

  await expectExpiredSessionLogout(page);
});

test('EDITPICK-08 an API error shows a toast for the Round Picks and for the save', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await draftInRoundTwo(context, setup);

  await failApiRequest(page, { method: 'GET', path: roundPicksPath(draft, 2) });
  await page.goto(editRoute(draft));
  await expectToast(page, { message: 'Unable to load picks' });

  const savePage = await context.newPage();
  await failApiRequest(savePage, { method: 'PUT', path: SAVE_PICK_PATH });
  await openEditPick(savePage, draft, 2);
  await editButtons(savePage).first().click();
  await chooseProPlayer(savePage, PRO_PLAYER.firstName, PRO_PLAYER_NAME);
  await updateButton(savePage).click();

  await expectToast(savePage, { title: 'Unable to edit pick', message: SERVER_ERROR_TOAST });
  await expect(updateButton(savePage)).toBeVisible();
});

// The access rows of the two routes differ only by the route
for (const { anonymousRow, otherCommissionerRow, route } of [
  { anonymousRow: 'ADDPICK-13', otherCommissionerRow: 'ADDPICK-14', route: addRoute },
  { anonymousRow: 'EDITPICK-09', otherCommissionerRow: 'EDITPICK-10', route: editRoute },
]) {
  test(`${anonymousRow} an anonymous viewer goes to the login screen`, async ({ page, setup }) => {
    knownFailureOnAngularJs('W-01');
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
    await setup.addPicks(commissioner, draft, 1);

    await page.goto(route(draft));

    await expectLoginRoute(page);
  });

  test(`${otherCommissionerRow} another Commissioner goes to the Draft index`, async ({ page, context, setup }) => {
    knownFailureOnAngularJs('W-02');
    const owner = await setup.registerCommissioner();
    const draft = await setup.createDraft(owner, { status: 'In Progress' });
    await setup.addPicks(owner, draft, 1);
    await setup.logInNewCommissioner(context);

    await page.goto(route(draft));

    await expect(page).toHaveURL(draftIndexRoute(draft));
  });
}
