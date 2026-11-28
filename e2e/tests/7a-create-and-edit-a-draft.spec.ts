import { Locator, Page } from '@playwright/test';
import { Draft, LoggedInUser, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { draftIndexRoute, visiblePanel } from '../helpers/draft-screen';
import { cancelButton, workingModal } from '../helpers/form';
import { failApiRequest, holdApiResponses } from '../helpers/intercept';
import { expectLoginRoute } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { VISIBLE_TEXT } from '../helpers/text';
import { expectToast } from '../helpers/toast';

// Route group 7A of docs/flow-inventory.md. Each test name starts with the row ID.

const CREATE_ROUTE = '/commish/draft/create';
const editRoute = (draft: Pick<Draft, 'id'>) => `/commish/draft/${draft.id}/edit`;
const POSITIONS_PATH = 'commish/depthchartposition/positions';

const nameField = (page: Page) => page.getByLabel('League Name');
const styleField = (page: Page) => page.getByLabel('Style');
const sportField = (page: Page) => page.getByLabel('Sport');
const roundsField = (page: Page) => page.getByLabel('# of Rounds');
const passwordField = (page: Page) => page.getByLabel('Password');
const depthChartsSwitch = (page: Page) => page.getByTestId('depth-charts-switch');
const createButton = (page: Page) => page.getByRole('button', { name: /Create League/ });
const saveButton = (page: Page) => page.getByRole('button', { name: /Save League/ });
const addRosterSpotButton = (page: Page) => page.getByRole('button', { name: /Add Roster Spot/ });
const positionRows = (page: Page) => page.getByTestId('depth-chart-position-row');
const slotsField = (page: Page, row: number) => positionRows(page).nth(row).getByTestId('depth-chart-slots');
const positionNameField = (page: Page, row: number) =>
  positionRows(page).nth(row).getByTestId('depth-chart-position-name');
const removeButton = (page: Page, row: number) =>
  positionRows(page).nth(row).getByTestId('remove-depth-chart-position');
const roundsTotal = (page: Page) => page.getByTestId('depth-chart-rounds-total');
const excludedNote = (page: Page) => page.getByTestId('excluded-positions-note');
const duplicateWarning = (page: Page) =>
  page.getByText('One or more of the depth chart positions has a duplicate position.');
const createHeading = (page: Page) => page.getByRole('heading', { name: 'Create a New League' });
const draftDetails = (page: Page) => visiblePanel(page, 'draft-details-panel');

const STYLE_LABELS = { serpentine: 'Serpentine Draft', standard: 'Standard Draft' };

/** The form is ready when the heading shows and the Draft defaults have loaded: the Sport menu holds its options. */
async function openCreateForm(page: Page) {
  await page.goto(CREATE_ROUTE);
  await expect(createHeading(page)).toBeVisible();
  await expect(sportField(page).locator('option', { hasText: 'Football (NFL)' })).toHaveCount(1);
}

async function openCreateFormFromHome(page: Page) {
  await page.goto('/home');
  await page.getByRole('link', { name: 'New League' }).click();
  await expect(page).toHaveURL(CREATE_ROUTE);
}

async function openEditFormFromIndex(page: Page, draft: Draft) {
  await page.goto(draftIndexRoute(draft));
  await visiblePanel(page, 'edit-draft-link').click();
  await expect(page).toHaveURL(editRoute(draft));
}

/** Clears the field, then moves the focus away, because the form shows its messages after the field loses focus. */
async function expectMessageAfterClear(field: Locator, message: string) {
  await field.fill('');
  await field.blur();
  await expect(field.page().getByText(message)).toBeVisible();
}

async function openEditForm(page: Page, draft: Draft) {
  await page.goto(editRoute(draft));
  await expect(nameField(page)).toHaveValue(draft.name);
}

async function fillCreateForm(
  page: Page,
  draft: { name: string; style?: keyof typeof STYLE_LABELS; league?: string; rounds?: number },
) {
  await nameField(page).fill(draft.name);
  await styleField(page).selectOption({ label: STYLE_LABELS[draft.style ?? 'serpentine'] });
  await sportField(page).selectOption({ label: draft.league ?? 'Football (NFL)' });

  if (draft.rounds !== undefined) {
    await roundsField(page).fill(String(draft.rounds));
  }
}

/** The create form of a Draft with Depth Charts on, with the default positions of NFL. */
async function openCreateFormWithDepthCharts(page: Page) {
  await openCreateForm(page);
  await sportField(page).selectOption({ label: 'Football (NFL)' });
  await depthChartsSwitch(page).click();
  await expect(positionRows(page).first()).toBeVisible();
}

async function slotValues(page: Page): Promise<number[]> {
  const fields = await positionRows(page).getByTestId('depth-chart-slots').all();

  return Promise.all(fields.map(async (field) => Number(await field.inputValue())));
}

async function positionNames(page: Page): Promise<string[]> {
  const fields = await positionRows(page).getByTestId('depth-chart-position-name').all();

  return Promise.all(fields.map((field) => field.inputValue()));
}

async function expectRoundsEqualSlots(page: Page) {
  const total = (await slotValues(page)).reduce((sum, slots) => sum + (Number.isNaN(slots) ? 0 : slots), 0);

  await expect(roundsTotal(page)).toHaveText(String(total));
}

/** The positions that the API gives for a League, plus the bench. */
async function defaultPositions(user: LoggedInUser, league: string): Promise<string[]> {
  const response = await user.api.get(`${POSITIONS_PATH}?draft_sport=${league}`);
  const { positions } = (await response.json()) as { positions: Record<string, string> };

  return [...Object.keys(positions), 'BN'];
}

async function expectIndexOf(page: Page, draft: Pick<Draft, 'id'>, details: RegExp[]) {
  await expect(page).toHaveURL(draftIndexRoute(draft));

  for (const detail of details) {
    await expect(draftDetails(page)).toContainText(detail, VISIBLE_TEXT);
  }
}

async function expectEmptyCreateForm(page: Page) {
  await expect(createHeading(page)).toBeVisible();
  await expect(nameField(page)).toBeVisible();
  await expect(styleField(page)).toBeVisible();
  await expect(sportField(page)).toBeVisible();
  await expect(depthChartsSwitch(page)).toBeVisible();
  await expect(roundsField(page)).toBeVisible();
  await expect(passwordField(page)).toBeVisible();
  await expect(createButton(page)).toBeDisabled();
  await expect(cancelButton(page)).toBeEnabled();
}

test('CREATE-01 the New League link opens the form with its fields and buttons', async ({ page, context, setup }) => {
  await setup.logInNewCommissioner(context);

  await openCreateFormFromHome(page);

  await expectEmptyCreateForm(page);
});

test('CREATE-02 a valid form creates a Draft and opens its Draft index', async ({ page, context, setup }) => {
  await setup.logInNewCommissioner(context);
  const name = unique('Created');
  const answerCreate = await holdApiResponses(page, { method: 'POST', path: 'commish/draft' });

  await openCreateForm(page);
  await fillCreateForm(page, { name, style: 'standard', rounds: 3 });
  await createButton(page).click();

  await expect(workingModal(page)).toBeVisible();
  answerCreate();
  await expect(workingModal(page)).toBeHidden();

  await expectToast(page, { message: `${name} created!` });
  await expect(page).toHaveURL(/\/draft\/\d+$/);
  await expect(draftDetails(page)).toContainText(name, VISIBLE_TEXT);
  await expect(draftDetails(page)).toContainText(/Draft Style:\s*Standard Draft/, VISIBLE_TEXT);
  await expect(draftDetails(page)).toContainText(/# of Rounds:\s*3/, VISIBLE_TEXT);
  await expect(page.getByText('Setting Up').first()).toBeVisible();
});

test('CREATE-03 a Draft with a password has the lock icon and a locked panel for an anonymous viewer', async ({
  page,
  context,
  setup,
  newViewerPage,
}) => {
  await setup.logInNewCommissioner(context);
  const name = unique('Created');

  await openCreateForm(page);
  await fillCreateForm(page, { name, rounds: 2 });
  await passwordField(page).fill('open-sesame');
  await createButton(page).click();
  await expect(page).toHaveURL(/\/draft\/\d+$/);
  const route = new URL(page.url()).pathname;

  await page.goto('/home');
  const row = page.getByTestId('draft-row').filter({ hasText: name });
  await expect(row.getByTestId('draft-lock-icon')).toBeVisible();

  const viewer = await newViewerPage();
  await viewer.goto(route);
  await expect(viewer.getByTestId('draft-locked-panel').getByText('Draft Locked')).toBeVisible();
  await expect(viewer.getByRole('dialog').getByRole('heading', { name: 'Draft is password protected' })).toBeVisible();
});

test('CREATE-04 Depth Charts on lists the default positions and makes # of Rounds the sum of the slots', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const expected = await defaultPositions(commissioner, 'NFL');

  await openCreateFormWithDepthCharts(page);

  await expect(page.getByText('Each team will need to draft:')).toBeVisible();
  await expect(positionRows(page)).toHaveCount(expected.length);
  expect(await positionNames(page)).toEqual(expected);
  expect(await slotValues(page)).toEqual(expected.map(() => 1));
  await expect(roundsField(page)).toBeHidden();
  await expect(roundsTotal(page)).toHaveText(String(expected.length));
});

test('CREATE-05 # of Rounds follows the slots after a change, an added row, and a removed row', async ({
  page,
  context,
  setup,
}) => {
  await setup.logInNewCommissioner(context);

  await openCreateFormWithDepthCharts(page);
  const rows = await positionRows(page).count();

  await slotsField(page, 0).fill('4');
  await expectRoundsEqualSlots(page);
  await expect(roundsTotal(page)).toHaveText(String(rows + 3));

  await addRosterSpotButton(page).click();
  await expect(positionRows(page)).toHaveCount(rows + 1);
  await positionNameField(page, rows).fill('XYZ');
  await slotsField(page, rows).fill('2');
  await expectRoundsEqualSlots(page);
  await expect(roundsTotal(page)).toHaveText(String(rows + 5));

  await removeButton(page, 0).click();
  await expect(positionRows(page)).toHaveCount(rows);
  await expectRoundsEqualSlots(page);
  await expect(roundsTotal(page)).toHaveText(String(rows + 1));
});

test('CREATE-06 a duplicate position name shows a message and disables Create League', async ({
  page,
  context,
  setup,
}) => {
  await setup.logInNewCommissioner(context);

  await openCreateFormWithDepthCharts(page);
  await nameField(page).fill(unique('Created'));
  await styleField(page).selectOption({ label: STYLE_LABELS.serpentine });
  await expect(duplicateWarning(page)).toBeHidden();
  await expect(createButton(page)).toBeEnabled();

  const [first] = await positionNames(page);
  await positionNameField(page, 1).fill(first);

  await expect(duplicateWarning(page)).toBeVisible();
  await expect(createButton(page)).toBeDisabled();
});

test('CREATE-07 the note about IR, DL and NA positions shows for IR, DL and NA, but not for DL in NFLE', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-23');
  const commissioner = await setup.logInNewCommissioner(context);
  const nfle = await defaultPositions(commissioner, 'NFLE');

  await openCreateFormWithDepthCharts(page);
  await expect(excludedNote(page)).toBeHidden();

  await positionNameField(page, 0).fill('IR');
  await expect(excludedNote(page)).toBeVisible();
  await expect(excludedNote(page)).toContainText(
    "IR, DL and NA positions generally don't count as active roster slots in most leagues, so you dont need to list them here.",
  );

  await positionNameField(page, 0).fill('QB');
  await expect(excludedNote(page)).toBeHidden();

  await positionNameField(page, 0).fill('NA');
  await expect(excludedNote(page)).toBeVisible();

  await positionNameField(page, 0).fill('DL');
  await expect(excludedNote(page)).toBeVisible();

  await sportField(page).selectOption({ label: 'Football - Extended Rosters (NFL)' });
  await expect(positionRows(page)).toHaveCount(nfle.length);
  await positionNameField(page, 0).fill('DL');
  await expect(excludedNote(page)).toBeHidden();
});

test('CREATE-08 Depth Charts off again makes # of Rounds an input, and the Draft has no positions', async ({
  page,
  context,
  setup,
}) => {
  await setup.logInNewCommissioner(context);
  const name = unique('Created');

  await openCreateFormWithDepthCharts(page);
  await expect(roundsField(page)).toBeHidden();
  await depthChartsSwitch(page).click();

  await expect(roundsField(page)).toBeVisible();
  await expect(roundsTotal(page)).toBeHidden();
  await nameField(page).fill(name);
  await styleField(page).selectOption({ label: STYLE_LABELS.serpentine });
  await roundsField(page).fill('5');
  await createButton(page).click();

  await expect(page).toHaveURL(/\/draft\/\d+$/);
  await expect(draftDetails(page)).toContainText(/Positions:\s*Off/, VISIBLE_TEXT);
});

test('CREATE-09 # of Rounds shows its three messages', async ({ page, context, setup }) => {
  await setup.logInNewCommissioner(context);

  await openCreateForm(page);
  await roundsField(page).fill('3');
  await expectMessageAfterClear(roundsField(page), 'Number of rounds is missing');

  await roundsField(page).fill('0');
  await expect(page.getByText('Your draft must have at least 1 round')).toBeVisible();
  await expect(page.getByText('Number of rounds is missing')).toBeHidden();

  await roundsField(page).fill('31');
  await expect(page.getByText('Too many rounds - max number of rounds is 30')).toBeVisible();
  await expect(page.getByText('Your draft must have at least 1 round')).toBeHidden();
});

test('CREATE-10 an empty League Name, Style, or Sport keeps Create League disabled and shows a message after the field loses focus', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-17');
  await setup.logInNewCommissioner(context);

  await openCreateForm(page);
  await fillCreateForm(page, { name: unique('Created'), rounds: 2 });
  await expect(createButton(page)).toBeEnabled();

  await expectMessageAfterClear(nameField(page), 'League name is missing');
  await expect(createButton(page)).toBeDisabled();

  await nameField(page).fill(unique('Created'));
  await styleField(page).focus();
  await styleField(page).selectOption({ index: 0 });
  await styleField(page).blur();
  await expect(createButton(page)).toBeDisabled();
  await expect(page.getByText('Style is missing')).toBeVisible();

  await styleField(page).selectOption({ label: STYLE_LABELS.serpentine });
  await sportField(page).focus();
  await sportField(page).selectOption({ index: 0 });
  await sportField(page).blur();
  await expect(createButton(page)).toBeDisabled();
  await expect(page.getByText('Sport is missing')).toBeVisible();
});

test('CREATE-11 a taken Draft name shows a toast and the form stays', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const taken = await setup.createDraft(commissioner);

  await openCreateForm(page);
  await fillCreateForm(page, { name: taken.name, rounds: 2 });
  await createButton(page).click();

  await expectToast(page, {
    title: 'Unable to create draft',
    message: 'Draft name is already taken, please choose a different name.',
  });
  await expect(page).toHaveURL(CREATE_ROUTE);
  await expect(nameField(page)).toHaveValue(taken.name);
});

test('CREATE-12 an anonymous viewer goes to the login screen', async ({ page }) => {
  knownFailureOnAngularJs('W-01');

  await page.goto(CREATE_ROUTE);

  await expectLoginRoute(page);
});

test('CREATE-13 Cancel goes back one step in the browser history', async ({ page, context, setup }) => {
  await setup.logInNewCommissioner(context);

  await openCreateFormFromHome(page);
  await cancelButton(page).click();

  await expect(page).toHaveURL(/\/home$/);
});

test('CREATE-14 an API error for the positions shows a toast', async ({ page, context, setup }) => {
  await setup.logInNewCommissioner(context);
  await openCreateForm(page);
  await failApiRequest(page, { method: 'GET', path: POSITIONS_PATH });

  await sportField(page).selectOption({ label: 'Football (NFL)' });
  await depthChartsSwitch(page).click();

  await expectToast(page, { message: 'Unable to load positions for the given draft sport' });
});

test('CREATE-15 an Admin gets the same form as a Commissioner', async ({ page, context, setup }) => {
  await setup.logInNewAdmin(context);

  await openCreateFormFromHome(page);

  await expectEmptyCreateForm(page);
});

test('EDIT-01 Edit on the Draft index opens the form with the values of the Draft', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, {
    name: unique('Editable'),
    style: 'standard',
    password: 'secret',
    depthChartPositions: [
      { position: 'QB', slots: 1 },
      { position: 'RB', slots: 2 },
    ],
  });

  await openEditFormFromIndex(page, draft);

  await expect(page.getByRole('heading', { name: `Editing ${draft.name}` })).toBeVisible();
  await expect(nameField(page)).toHaveValue(draft.name);
  await expect(styleField(page).locator('option:checked')).toHaveText(STYLE_LABELS.standard);
  await expect(sportField(page).locator('option:checked')).toHaveText('Football (NFL)');
  await expect(passwordField(page)).toHaveValue('secret');
  await expect(positionRows(page)).toHaveCount(2);
  expect(await positionNames(page)).toEqual(['QB', 'RB']);
  expect(await slotValues(page)).toEqual([1, 2]);
  await expect(roundsTotal(page)).toHaveText('3');
  await expect(saveButton(page)).toBeVisible();
  await expect(cancelButton(page)).toBeVisible();
});

test('EDIT-02 Save League stores the new name, Draft Style, and rounds', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, { style: 'serpentine', rounds: 2 });
  const newName = unique('Renamed');

  await openEditForm(page, draft);
  await nameField(page).fill(newName);
  await styleField(page).selectOption({ label: STYLE_LABELS.standard });
  await roundsField(page).fill('6');
  await saveButton(page).click();

  await expectToast(page, { message: `${newName} edited!` });
  await expectIndexOf(page, draft, [new RegExp(newName), /Draft Style:\s*Standard Draft/, /# of Rounds:\s*6/]);
});

test('EDIT-03 Depth Charts on a saved Draft follow the rules of the create form', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner);
  const expected = await defaultPositions(commissioner, 'NFL');

  await openEditForm(page, draft);
  await depthChartsSwitch(page).click();
  await expect(positionRows(page)).toHaveCount(expected.length);
  expect(await positionNames(page)).toEqual(expected);
  await expectRoundsEqualSlots(page);

  await positionNameField(page, 1).fill(expected[0]);
  await expect(duplicateWarning(page)).toBeVisible();
  await expect(saveButton(page)).toBeDisabled();

  await removeButton(page, 1).click();
  await expect(duplicateWarning(page)).toBeHidden();
  await slotsField(page, 0).fill('3');
  await expectRoundsEqualSlots(page);

  await addRosterSpotButton(page).click();
  const added = (await positionRows(page).count()) - 1;
  await positionNameField(page, added).fill('XYZ');
  await slotsField(page, added).fill('2');
  await expectRoundsEqualSlots(page);
  const names = await positionNames(page);
  const slots = await slotValues(page);
  await saveButton(page).click();

  await expectIndexOf(
    page,
    draft,
    names.map((name, index) => new RegExp(`${slots[index]}×\\s*${name}`)),
  );
});

test('EDIT-04 an empty League Name, an empty # of Rounds, and 31 disable Save League and show a message', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-17');
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner);

  await openEditForm(page, draft);
  await expectMessageAfterClear(nameField(page), 'League name is missing');
  await expect(saveButton(page)).toBeDisabled();

  await expectMessageAfterClear(roundsField(page), 'Number of rounds is missing');

  await roundsField(page).fill('31');
  await expect(saveButton(page)).toBeDisabled();
  await expect(page.getByText('Too many rounds - max number of rounds is 30')).toBeVisible();
});

test('EDIT-05 a name that another Draft holds shows the API message in a toast', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const taken = await setup.createDraft(commissioner, { name: unique('Taken') });
  const draft = await setup.createDraft(commissioner);

  await openEditForm(page, draft);
  await nameField(page).fill(taken.name);
  await saveButton(page).click();

  await expectToast(page, {
    title: 'Unable to edit draft',
    message: 'Draft name is already taken, please choose a different name.',
  });
  await expect(page).toHaveURL(editRoute(draft));
});

test('EDIT-06 Cancel goes back one step in the browser history', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner);

  await openEditFormFromIndex(page, draft);
  await cancelButton(page).click();

  await expect(page).toHaveURL(draftIndexRoute(draft));
});

test('EDIT-07 the edit route of an In Progress or Complete Draft warns and goes to the Draft index', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-15');
  const commissioner = await setup.logInNewCommissioner(context);
  const inProgress = await setup.createDraft(commissioner, { status: 'In Progress' });
  const complete = await setup.createDraft(commissioner, { status: 'Complete' });

  for (const draft of [inProgress, complete]) {
    await page.goto(editRoute(draft));

    await expectToast(page, { message: /^Unable to edit draft/ });
    await expect(page).toHaveURL(draftIndexRoute(draft));
  }
});

test('EDIT-08 an Admin edits the Draft of another user', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { password: 'secret' });
  await setup.logInNewAdmin(context);
  const newName = unique('Renamed');

  await openEditForm(page, draft);
  await expect(page.getByRole('heading', { name: `Editing ${draft.name}` })).toBeVisible();
  await expect(passwordField(page)).toHaveValue('secret');
  await nameField(page).fill(newName);
  await saveButton(page).click();

  await expectToast(page, { message: `${newName} edited!` });
  await expectIndexOf(page, draft, [new RegExp(newName)]);
});

test('EDIT-09 an anonymous viewer goes to the login screen', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-01');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner);

  await page.goto(editRoute(draft));

  await expectLoginRoute(page);
});

test('EDIT-10 another Commissioner goes to the Draft index', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-02');
  const owner = await setup.registerCommissioner();
  const draft = await setup.createDraft(owner);
  await setup.logInNewCommissioner(context);

  await page.goto(editRoute(draft));

  await expect(page).toHaveURL(draftIndexRoute(draft));
});
