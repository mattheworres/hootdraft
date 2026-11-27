import { BrowserContext, Page } from '@playwright/test';
import { ApiSetup, Draft, DraftOptions, LoggedInUser, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { expectInProgressCommishView, openDraftScreen, openRoute, visiblePanel } from '../helpers/draft-screen';
import {
  expectLoadingUntilReleased,
  failApiRequest,
  holdApiResponses,
  recordApiRequests,
  waitForApiResponse,
} from '../helpers/intercept';
import { expect, test } from '../helpers/test';
import { expectToast } from '../helpers/toast';

// Route group 7B of docs/flow-inventory.md. Each test name starts with the row ID.

const NO_MANAGERS = 'There are no managers in your league right now.';
const ORDINALS = ['1st', '2nd', '3rd'];
// When the client's Randomize Order timers fire for three Managers, in ms after the confirm
const RANDOMIZE_COUNTDOWN_AT = [
  { text: '3…', at: 1_750 },
  { text: '2…', at: 3_550 },
  { text: '1…', at: 5_350 },
];
const RANDOMIZE_MANAGERS_AT = [7_150, 8_950, 10_350];
const RANDOMIZE_BUTTONS_AT = 11_100;
// ng-show of the AngularJS client waits for animation frames, which run only while the clock moves
const ANIMATION_MS = 200;

const orderPanel = (page: Page) => visiblePanel(page, 'draft-order-panel');
const managerRows = (page: Page) => orderPanel(page).getByTestId('manager-row').filter({ visible: true });
const managerRow = (page: Page, index: number) => managerRows(page).nth(index);
const managerNames = (page: Page) => managerRows(page).getByTestId('manager-name');
const randomizeButton = (page: Page) => orderPanel(page).getByRole('button', { name: 'Randomize Order' });
const addManagersButton = (page: Page) => orderPanel(page).getByRole('button', { name: 'Add Managers' });
const addManagersLink = (page: Page) => orderPanel(page).getByRole('link', { name: 'Go ahead and add some.' });
const dialog = (page: Page) => page.getByRole('dialog');
const addManagersDialog = (page: Page) =>
  dialog(page).filter({ has: page.getByRole('heading', { name: 'Add Managers' }) });
const nameInputs = (page: Page) => addManagersDialog(page).getByPlaceholder('New manager/team name');
const removeRowButtons = (page: Page) => addManagersDialog(page).getByTestId('remove-manager-row');
const dialogSave = (page: Page) => addManagersDialog(page).getByRole('button', { name: 'Save' });
const dialogCancel = (page: Page) => addManagersDialog(page).getByRole('button', { name: 'Cancel' });
const dialogAdd = (page: Page) => addManagersDialog(page).getByRole('button', { name: /Add$/ });
const nameInput = (page: Page, index: number) => managerRow(page, index).getByTestId('manager-name-input');
const editIcon = (page: Page, index: number) => managerRow(page, index).getByTestId('edit-manager');
const deleteIcon = (page: Page, index: number) => managerRow(page, index).getByTestId('delete-manager');
const saveIcon = (page: Page, index: number) => managerRow(page, index).getByTestId('save-manager');
const cancelIcon = (page: Page, index: number) => managerRow(page, index).getByTestId('cancel-manager-edit');

const commishActions = (page: Page) => visiblePanel(page, 'commish-actions-panel');
const startButton = (page: Page) => commishActions(page).getByRole('button', { name: 'Start!' });
const resetButton = (page: Page) => commishActions(page).getByRole('button', { name: 'Reset' });
const deleteButton = (page: Page) => commishActions(page).getByRole('button', { name: 'Delete' });
const toolbox = (page: Page) => page.getByRole('button', { name: 'Commissioner Toolbox' });
const toolboxItem = (page: Page, name: string) => page.getByTestId('commissioner-toolbox-menu').getByText(name);
const promptDialog = (page: Page, title: string) =>
  dialog(page).filter({ has: page.getByRole('heading', { name: title }) });

interface Prompt {
  title: string;
  confirm: string;
}

const START_PROMPT: Prompt = { title: 'Start draft?', confirm: "Yep! Let's do this!" };
const RESET_PROMPT: Prompt = { title: 'Reset draft?', confirm: 'Yes, reset my draft' };
const DELETE_PROMPT: Prompt = { title: 'Delete the draft?', confirm: 'Yes, Delete the draft' };
const RANDOMIZE_PROMPT: Prompt = { title: 'Randomize the draft order?', confirm: 'Yes, Randomize this draft!' };

const draftIndexRoute = (draft: Draft) => `/draft/${draft.id}`;
const commishRoute = (path: string) => (draft: Draft) => `/commish/draft/${draft.id}/${path}`;
const publicRoute = (path: string) => (draft: Draft) => `${draftIndexRoute(draft)}/${path}`;

const EDIT_ROUTES = [
  { name: 'Edit Draft', route: commishRoute('edit') },
  { name: 'Round Times', route: commishRoute('timers') },
];
// The screens that a Setting Up Draft allows. The other Draft screens send its viewers back to the index.
const SETTING_UP_ROUTES = [{ name: 'the Draft index', route: draftIndexRoute }, ...EDIT_ROUTES];
// Each screen of an In Progress Draft other than the Draft index. Draft Stats is left out: it sends the viewer of an
// In Progress Draft away (STATS-02).
const IN_PROGRESS_ROUTES = [
  ...EDIT_ROUTES,
  { name: 'Enter Picks', route: commishRoute('picks/add') },
  { name: 'Edit a Pick', route: commishRoute('picks/edit') },
  { name: 'Enter a Trade', route: commishRoute('trades/add') },
  { name: 'the Draft Board', route: publicRoute('board') },
  { name: 'Picks by Manager', route: publicRoute('picks/manager') },
  { name: 'Picks by Round', route: publicRoute('picks/round') },
  { name: 'Search', route: publicRoute('search') },
  { name: 'Trades', route: publicRoute('trades') },
  { name: 'Depth Chart', route: publicRoute('depth_chart') },
];

const managerPath = (draft: Draft, managerId: number) => `commish/draft/${draft.id}/manager/${managerId}`;
const managersPath = (draft: Draft) => `commish/draft/${draft.id}/managers`;
const reorderPath = (draft: Draft) => `${managersPath(draft)}/reorder`;
const statusPath = (draft: Draft) => `commish/draft/${draft.id}/status`;
const draftPath = (draft: Draft) => `commish/draft/${draft.id}`;

const settingUpDraft = (setup: ApiSetup, commissioner: LoggedInUser, managers: string[]) =>
  setup.createDraft(commissioner, { status: 'Setting Up', managers });

const threeManagers = () => [unique('Ann'), unique('Bob'), unique('Cy')];

async function openIndex(page: Page, draft: Draft) {
  await openDraftScreen(page, draft);
  if (draft.managers.length > 0) {
    await expect(managerRows(page)).toHaveCount(draft.managers.length);
  }
}

async function openIndexAsNewCommissioner(page: Page, context: BrowserContext, setup: ApiSetup, managers: string[]) {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await settingUpDraft(setup, commissioner, managers);

  await openIndex(page, draft);

  return { commissioner, draft };
}

async function expectOrder(page: Page, names: string[]) {
  await expect(managerNames(page)).toHaveText(names);
  for (const [index, ordinal] of ORDINALS.slice(0, names.length).entries()) {
    await expect(managerRow(page, index)).toContainText(ordinal);
  }
}

async function visibleNames(page: Page): Promise<string[]> {
  return (await managerNames(page).allTextContents()).map((name) => name.trim());
}

/**
 * Drags a Manager row onto another, with mouse events that the page script sends. The rows are also HTML5 draggable,
 * so a real mouse drag starts a native drag that swallows the mouse events of the list library (slip.js).
 */
async function dragRow(page: Page, from: number, to: number) {
  const targetBox = await managerRow(page, to).boundingBox();

  if (!targetBox) {
    throw new Error('The Manager row has no box.');
  }

  const endY = to < from ? targetBox.y + 5 : targetBox.y + targetBox.height - 5;

  await managerRow(page, from).evaluate(async (row, toY) => {
    const grip = row.querySelector('.manager_name') as HTMLElement;
    const box = row.getBoundingClientRect();
    const x = box.x + box.width / 2;
    const startY = box.y + box.height / 2;
    const steps = 20;
    const fire = (type: string, element: EventTarget, y: number) =>
      element.dispatchEvent(
        new MouseEvent(type, { bubbles: true, cancelable: true, button: 0, clientX: x, clientY: y, view: window }),
      );

    fire('mousedown', grip, startY);
    for (let step = 1; step <= steps; step++) {
      fire('mousemove', document.documentElement, startY + ((toY - startY) * step) / steps);
      await new Promise((resolve) => setTimeout(resolve, 20));
    }
    fire('mouseup', document.documentElement, toY);
  }, endY);
}

/**
 * Returns a function that moves the paused clock to `at` ms after now, plus the time of the animations. Each move
 * counts from the same start, so the margin does not add up.
 */
function clockTimeline(page: Page) {
  let elapsed = 0;

  return async (at: number) => {
    await page.clock.runFor(at + ANIMATION_MS - elapsed);
    elapsed = at + ANIMATION_MS;
  };
}

async function openAddDialogOnEmptyIndex(page: Page, context: BrowserContext, setup: ApiSetup) {
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, []);
  await addManagersLink(page).click();
  await expect(addManagersDialog(page)).toBeVisible();

  return draft;
}

async function startEdit(page: Page, index: number) {
  await editIcon(page, index).click();
  await expect(nameInput(page, index)).toBeVisible();
}

async function pressPromptButton(page: Page, prompt: Prompt, name: string) {
  await expect(promptDialog(page, prompt.title)).toBeVisible();
  await promptDialog(page, prompt.title).getByRole('button', { name }).click();
}

const confirmPrompt = (page: Page, prompt: Prompt) => pressPromptButton(page, prompt, prompt.confirm);
const dismissPrompt = (page: Page, prompt: Prompt, button: 'Cancel' | 'Close') =>
  pressPromptButton(page, prompt, button);

async function expectNoPrompt(page: Page) {
  await expect(dialog(page)).toHaveCount(0);
}

async function expectDraftStarted(page: Page, setup: ApiSetup, commissioner: LoggedInUser, draft: Draft) {
  await expectToast(page, { message: 'Draft started' });
  expect(await setup.draftStatus(commissioner, draft)).toBe('In Progress');
}

interface DraftToDelete {
  draft: Draft;
  kept: Draft;
  tag: string;
}

/**
 * A Draft to delete, and a Draft of the same Commissioner that stays. Both names hold one tag. The table of /home shows
 * "No drafts here" before the Draft list arrives, so only a row of the kept Draft proves that the table has loaded.
 */
async function createDraftToDelete(
  setup: ApiSetup,
  commissioner: LoggedInUser,
  options: DraftOptions,
): Promise<DraftToDelete> {
  const tag = unique('Delete');
  const kept = await setup.createDraft(commissioner, { name: `${tag} kept` });
  const draft = await setup.createDraft(commissioner, { ...options, name: `${tag} deleted` });

  return { draft, kept, tag };
}

async function expectDraftDeleted(page: Page, { draft, kept, tag }: DraftToDelete) {
  const draftRows = page.getByTestId('draft-row');

  await expectToast(page, { message: 'Draft deleted' });
  await expect(page).toHaveURL('/home');
  await page.getByRole('textbox', { name: 'Search Leagues...' }).fill(tag);
  await expect(draftRows.first()).toContainText(kept.name);
  await expect(draftRows).toHaveCount(1);
  await expect(draftRows.filter({ hasText: draft.name })).toHaveCount(0);
}

async function expectDismissKeepsDraft(
  page: Page,
  context: BrowserContext,
  setup: ApiSetup,
  button: 'Cancel' | 'Close',
) {
  const commissioner = await setup.logInNewCommissioner(context);
  const settingUp = await settingUpDraft(setup, commissioner, threeManagers());
  const inProgress = await setup.createDraft(commissioner, { status: 'In Progress' });
  const cases = [
    { draft: settingUp, open: startButton, prompt: START_PROMPT, status: 'Setting Up' },
    { draft: inProgress, open: resetButton, prompt: RESET_PROMPT, status: 'In Progress' },
    { draft: inProgress, open: deleteButton, prompt: DELETE_PROMPT, status: 'In Progress' },
  ] as const;

  for (const { draft, open, prompt, status } of cases) {
    await openDraftScreen(page, draft);
    await open(page).click();
    await dismissPrompt(page, prompt, button);
    await expectNoPrompt(page);
    expect(await setup.draftStatus(commissioner, draft)).toBe(status);
  }
}

test('MGR-01 a Draft with no Managers shows a link that opens the dialog "Add Managers"', async ({
  page,
  context,
  setup,
}) => {
  await openIndexAsNewCommissioner(page, context, setup, []);

  await expect(orderPanel(page)).toContainText(NO_MANAGERS);
  await addManagersLink(page).click();
  await expect(addManagersDialog(page)).toBeVisible();
});

test('MGR-02 the dialog adds three Managers', async ({ page, context, setup }) => {
  const [first, second, third] = threeManagers();
  await openAddDialogOnEmptyIndex(page, context, setup);

  await nameInputs(page).first().fill(first);
  await dialogAdd(page).click();
  await nameInputs(page).nth(1).fill(second);
  await dialogAdd(page).click();
  await nameInputs(page).nth(2).fill(third);
  await dialogSave(page).click();

  await expectToast(page, { message: 'Managers added' });
  await expect(addManagersDialog(page)).toHaveCount(0);
  await expectOrder(page, [first, second, third]);
  await expect(randomizeButton(page)).toBeVisible();
  await expect(addManagersButton(page)).toBeVisible();
});

test('MGR-03 the dialog saves only the rows with a name, and closes with no request when no row has one', async ({
  page,
  context,
  setup,
}) => {
  const kept = unique('Kept');
  const draft = await openAddDialogOnEmptyIndex(page, context, setup);
  await dialogAdd(page).click();
  await dialogAdd(page).click();
  await expect(nameInputs(page)).toHaveCount(3);
  await nameInputs(page).nth(0).fill(kept);
  await nameInputs(page).nth(1).fill(unique('Removed'));

  await removeRowButtons(page).nth(1).click();
  await expect(nameInputs(page)).toHaveCount(2);
  await dialogSave(page).click();

  await expectToast(page, { message: 'Managers added' });
  await expectOrder(page, [kept]);

  const requests = recordApiRequests(page, { method: 'POST', path: managersPath(draft) });
  await addManagersButton(page).click();
  await expect(addManagersDialog(page)).toBeVisible();
  await dialogSave(page).click();

  await expect(addManagersDialog(page)).toHaveCount(0);
  expect(requests).toEqual([]);
});

test('MGR-04 Cancel closes the dialog and sends no request', async ({ page, context, setup }) => {
  const draft = await openAddDialogOnEmptyIndex(page, context, setup);
  const requests = recordApiRequests(page, { method: 'POST', path: managersPath(draft) });
  await nameInputs(page).first().fill(unique('Ignored'));

  await dialogCancel(page).click();

  await expect(addManagersDialog(page)).toHaveCount(0);
  expect(requests).toEqual([]);
});

test('MGR-05 an API error on the add request shows a toast and keeps the dialog open', async ({
  page,
  context,
  setup,
}) => {
  const draft = await openAddDialogOnEmptyIndex(page, context, setup);
  await failApiRequest(page, { method: 'POST', path: managersPath(draft) });
  await nameInputs(page).first().fill(unique('Ann'));

  await dialogSave(page).click();

  await expectToast(page, { message: 'Unable to add managers' });
  await expect(addManagersDialog(page)).toBeVisible();
});

test('MGR-06 the edit icon turns a name into an input, and Enter or the save icon saves it', async ({
  page,
  context,
  setup,
}) => {
  const names = threeManagers();
  await openIndexAsNewCommissioner(page, context, setup, names);
  const entered = unique('Enter');
  const clicked = unique('Click');

  await startEdit(page, 0);
  await nameInput(page, 0).fill(entered);
  await nameInput(page, 0).press('Enter');

  await expectToast(page, { message: `${entered} updated!` });
  await expect(nameInput(page, 0)).toBeHidden();
  await expect(managerNames(page).first()).toHaveText(entered);

  await startEdit(page, 1);
  await nameInput(page, 1).fill(clicked);
  await saveIcon(page, 1).click();

  await expectToast(page, { message: `${clicked} updated!` });
  await expect(nameInput(page, 1)).toBeHidden();
  await expectOrder(page, [entered, clicked, names[2]]);
});

test('MGR-07 the cancel icon restores the name with no request, and other icons do nothing during an edit', async ({
  page,
  context,
  setup,
}) => {
  const names = threeManagers();
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, names);
  const updates = recordApiRequests(page, { method: 'PUT', path: managerPath(draft, draft.managers[0].id) });
  const deletes = recordApiRequests(page, { method: 'DELETE' });

  const changed = unique('Changed');

  await startEdit(page, 0);
  await nameInput(page, 0).fill(changed);
  await deleteIcon(page, 1).click();
  await editIcon(page, 1).click();
  // A click runs its change detection before click() returns, so these checks see the result of both clicks
  await expect(nameInput(page, 0)).toHaveValue(changed);
  await expect(editIcon(page, 1)).toBeVisible();
  await expect(nameInput(page, 1)).toBeHidden();
  await expect(managerRows(page)).toHaveCount(3);
  await cancelIcon(page, 0).click();

  await expect(nameInput(page, 0)).toBeHidden();
  await expectOrder(page, names);
  expect(updates).toEqual([]);
  expect(deletes).toEqual([]);
});

test('MGR-08 an API error on the update request shows a toast and keeps the input', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, threeManagers());
  await failApiRequest(page, { method: 'PUT', path: managerPath(draft, draft.managers[0].id) });

  await startEdit(page, 0);
  await nameInput(page, 0).fill(unique('Changed'));
  await saveIcon(page, 0).click();

  await expectToast(page, { message: 'Unable to update manager' });
  await expect(nameInput(page, 0)).toBeVisible();
});

test('MGR-09 the delete icon removes a Manager at once, with no prompt', async ({ page, context, setup }) => {
  const [first, second, third] = threeManagers();
  await openIndexAsNewCommissioner(page, context, setup, [first, second, third]);

  await deleteIcon(page, 1).click();

  await expectOrder(page, [first, third]);
  await expectNoPrompt(page);
});

test('MGR-10 an API error on the delete request shows a toast and puts the Manager back', async ({
  page,
  context,
  setup,
}) => {
  const names = threeManagers();
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, names);
  await failApiRequest(page, { method: 'DELETE', path: managerPath(draft, draft.managers[1].id) });

  await deleteIcon(page, 1).click();

  await expectToast(page, { message: 'Unable to delete manager' });
  await expectOrder(page, names);
});

test('MGR-11 dragging a Manager saves the new Draft Order', async ({ page, context, setup }) => {
  const [first, second, third] = threeManagers();
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, [first, second, third]);
  const release = await holdApiResponses(page, { method: 'PUT', path: reorderPath(draft) });
  const saved = waitForApiResponse(page, { method: 'PUT', path: reorderPath(draft) });

  await dragRow(page, 2, 0);

  await expectLoadingUntilReleased(orderPanel(page), release, { text: 'Saving draft order...' });
  await saved;
  await expectOrder(page, [third, first, second]);

  await page.reload();
  await expect(managerRows(page)).toHaveCount(3);
  await expectOrder(page, [third, first, second]);
});

test('MGR-12 an API error on the reorder request shows a toast', async ({ page, context, setup }) => {
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, threeManagers());
  await failApiRequest(page, { method: 'PUT', path: reorderPath(draft) });

  await dragRow(page, 2, 0);

  await expectToast(page, { message: 'Unable to reorder managers' });
});

test('MGR-13 Randomize Order asks to confirm, shows a countdown, and saves the new Draft Order', async ({
  page,
  context,
  setup,
}) => {
  const names = threeManagers();
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await settingUpDraft(setup, commissioner, names);
  await page.clock.install();
  await openIndex(page, draft);
  // Until now the clock ran in real time
  await page.clock.pauseAt(new Date(Date.now() + 1_000));
  const reordered = waitForApiResponse(page, { method: 'PUT', path: reorderPath(draft) });
  const countdown = page.getByTestId('randomize-countdown');

  await randomizeButton(page).click();
  await confirmPrompt(page, RANDOMIZE_PROMPT);
  await reordered;
  const runTo = clockTimeline(page);

  await runTo(0);
  await expect(managerNames(page)).toHaveCount(0);
  await expect(randomizeButton(page)).toBeHidden();
  for (const { text, at } of RANDOMIZE_COUNTDOWN_AT) {
    await runTo(at);
    await expect(countdown).toHaveText(text);
    await expect(managerNames(page)).toHaveCount(0);
  }
  for (const [index, at] of RANDOMIZE_MANAGERS_AT.entries()) {
    await runTo(at);
    await expect(countdown).toBeHidden();
    await expect(managerNames(page)).toHaveCount(index + 1);
    await expect(randomizeButton(page)).toBeHidden();
  }
  await runTo(RANDOMIZE_BUTTONS_AT);
  await expect(randomizeButton(page)).toBeVisible();
  await expect(addManagersButton(page)).toBeVisible();

  const shown = await visibleNames(page);
  expect(shown.toSorted()).toEqual(names.toSorted());
  await expectOrder(page, shown);
  await page.clock.resume();
  await page.reload();
  await expect(managerRows(page)).toHaveCount(3);
  await expectOrder(page, shown);
});

test('MGR-14 Cancel closes the randomize prompt and keeps the Draft Order', async ({ page, context, setup }) => {
  const names = threeManagers();
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, names);
  const requests = recordApiRequests(page, { method: 'PUT', path: reorderPath(draft) });

  await randomizeButton(page).click();
  await dismissPrompt(page, RANDOMIZE_PROMPT, 'Cancel');

  await expectNoPrompt(page);
  await expectOrder(page, names);
  expect(requests).toEqual([]);
});

test('MGR-15 an API error on the reorder request of Randomize Order shows two toasts', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await openIndexAsNewCommissioner(page, context, setup, threeManagers());
  await failApiRequest(page, { method: 'PUT', path: reorderPath(draft) });

  await randomizeButton(page).click();
  await confirmPrompt(page, RANDOMIZE_PROMPT);

  await expectToast(page, { message: 'Unable to reorder managers' });
  await expectToast(page, { message: 'Unable to randomize draft order - error while saving managers.' });
});

test('MGR-16 a slow Managers request shows a loading indicator in "Draft Order"', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-24');
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await settingUpDraft(setup, commissioner, threeManagers());
  const release = await holdApiResponses(page, { method: 'GET', path: managersPath(draft) });

  await page.goto(`/draft/${draft.id}`);

  await expectLoadingUntilReleased(orderPanel(page), release);
  await expect(managerRows(page)).toHaveCount(3);
});

test('STATUS-01 Start! asks to confirm, then starts the Draft', async ({ page, context, setup }) => {
  const { commissioner, draft } = await openIndexAsNewCommissioner(page, context, setup, threeManagers());

  await startButton(page).click();
  await confirmPrompt(page, START_PROMPT);

  await expectDraftStarted(page, setup, commissioner, draft);
  await expectInProgressCommishView(page);
});

test('STATUS-02 Start! with fewer than two Managers shows an error and keeps the Draft Setting Up', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, draft } = await openIndexAsNewCommissioner(page, context, setup, [unique('Ann')]);

  await startButton(page).click();
  await confirmPrompt(page, START_PROMPT);

  await expectToast(page, {
    message: /^Unable to start draft\s+A draft must have at least 2 managers before it can begin\.$/,
  });
  expect(await setup.draftStatus(commissioner, draft)).toBe('Setting Up');
  await expect(startButton(page)).toBeVisible();
});

for (const { name, route } of SETTING_UP_ROUTES) {
  test(`STATUS-03 Start Draft! in the Commissioner Toolbox of ${name} starts the Draft`, async ({
    page,
    context,
    setup,
  }) => {
    const commissioner = await setup.logInNewCommissioner(context);
    const draft = await settingUpDraft(setup, commissioner, threeManagers());
    await openRoute(page, route(draft), toolbox(page));

    await toolbox(page).click();
    await toolboxItem(page, 'Start Draft!').click();
    await confirmPrompt(page, START_PROMPT);

    await expectDraftStarted(page, setup, commissioner, draft);
    await openDraftScreen(page, draft);
    await expectInProgressCommishView(page);
  });
}

test('STATUS-04 Reset asks to confirm, returns to Setting Up, and deletes Picks and Trades', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', rounds: 3 });
  await setup.addPicks(commissioner, draft, 2);
  await setup.addTrade(commissioner, draft, {
    manager1Id: draft.managers[0].id,
    manager2Id: draft.managers[1].id,
    pickIds: await setup.upcomingPickIds(commissioner, draft, 1),
  });
  await openDraftScreen(page, draft);

  await resetButton(page).click();
  await confirmPrompt(page, RESET_PROMPT);

  await expectToast(page, { message: 'Draft reset' });
  await expect(startButton(page)).toBeVisible();
  await setup.setDraftStatus(commissioner, draft, 'In Progress');
  expect(await setup.lastSelectedPickIds(commissioner, draft)).toEqual([]);
  const current = await setup.currentPick(commissioner, draft);
  expect([current.round, current.number]).toEqual([1, 1]);
  expect(await setup.tradeIds(commissioner, draft)).toEqual([]);
});

for (const { name, route } of IN_PROGRESS_ROUTES) {
  test(`STATUS-05 Reset Draft in the Commissioner Toolbox of ${name} resets the Draft and goes to the Draft index`, async ({
    page,
    context,
    setup,
  }) => {
    const commissioner = await setup.logInNewCommissioner(context);
    const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
    await openRoute(page, route(draft), toolbox(page));

    await toolbox(page).click();
    await toolboxItem(page, 'Reset Draft').click();
    await confirmPrompt(page, RESET_PROMPT);

    await expectToast(page, { message: 'Draft reset' });
    await expect(page).toHaveURL(`/draft/${draft.id}`);
  });
}

for (const status of ['Setting Up', 'In Progress'] as const) {
  test(`STATUS-06 Delete asks to confirm, deletes a ${status} Draft, and goes to /home`, async ({
    page,
    context,
    setup,
  }) => {
    const commissioner = await setup.logInNewCommissioner(context);
    const managers = status === 'Setting Up' ? threeManagers() : undefined;
    const toDelete = await createDraftToDelete(setup, commissioner, { status, managers });
    await openDraftScreen(page, toDelete.draft);

    await deleteButton(page).click();
    await confirmPrompt(page, DELETE_PROMPT);

    await expectDraftDeleted(page, toDelete);
  });
}

test('STATUS-07 Delete Draft in the Commissioner Toolbox deletes a Complete Draft', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const toDelete = await createDraftToDelete(setup, commissioner, { status: 'Complete', rounds: 1 });
  await openDraftScreen(page, toDelete.draft);

  await toolbox(page).click();
  await toolboxItem(page, 'Delete Draft').click();
  await confirmPrompt(page, DELETE_PROMPT);

  await expectDraftDeleted(page, toDelete);
});

test('STATUS-08 Cancel closes a confirm prompt and leaves the Draft unchanged', async ({ page, context, setup }) => {
  await expectDismissKeepsDraft(page, context, setup, 'Cancel');
});

test('STATUS-08 the close button (×) closes a confirm prompt and leaves the Draft unchanged', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-10');

  await expectDismissKeepsDraft(page, context, setup, 'Close');
});

test('STATUS-09 an API error on the status request or the delete request shows a toast', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await openDraftScreen(page, draft);
  await failApiRequest(page, { method: 'PUT', path: statusPath(draft), body: { errors: ['The API failed.'] } });
  await failApiRequest(page, { method: 'DELETE', path: draftPath(draft) });

  await resetButton(page).click();
  await confirmPrompt(page, RESET_PROMPT);
  await expectToast(page, { message: /^Unable to reset draft/ });

  await deleteButton(page).click();
  await confirmPrompt(page, DELETE_PROMPT);
  await expectToast(page, { message: 'Unable to delete draft' });
  await expect(page).toHaveURL(`/draft/${draft.id}`);
});

test('STATUS-10 an Admin starts the Draft of a different user', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await settingUpDraft(setup, commissioner, [unique('Ann'), unique('Bob')]);
  await setup.logInNewAdmin(context);
  await openIndex(page, draft);

  await startButton(page).click();
  await confirmPrompt(page, START_PROMPT);

  await expectDraftStarted(page, setup, commissioner, draft);
  await expectInProgressCommishView(page);
});
