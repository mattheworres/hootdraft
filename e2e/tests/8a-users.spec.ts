import { BrowserContext, Locator, Page } from '@playwright/test';
import { ApiSetup, Credentials, emailFor, LoggedInUser, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { emailField, workingModal } from '../helpers/form';
import { failApiRequest, holdApiResponses, recordApiRequests } from '../helpers/intercept';
import { expectLoginRoute } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { EXPIRED_SESSION_TOAST, expectToast } from '../helpers/toast';

// Route group 8A of docs/flow-inventory.md. Each test name starts with the row ID.

const PAGE_SIZE = 15;
const USERS_PATH = 'admin/users';
const USER_PATH = /^admin\/user\/\d+$/;

interface SeedUser extends Credentials {
  enabled: boolean;
}

/** A tag that only the users of one test hold. The email search with its slug shows only those users. */
function newTag() {
  const tag = unique('Usr');

  return { tag, slug: tag.toLowerCase().replaceAll(' ', '-') };
}

const nameSearch = (page: Page) => page.getByRole('textbox', { name: 'Search names...' });
const enabledFilter = (page: Page) => page.getByTestId('user-enabled-filter');
const emailSearch = (page: Page) => page.getByRole('textbox', { name: 'Search emails...' });
const userRows = (page: Page) => page.getByRole('row').filter({ has: page.getByRole('button', { name: 'Edit' }) });
// The sort icons are part of the accessible name of a header
const header = (page: Page, name: string) =>
  page.getByRole('columnheader', { name: new RegExp(`${name.replace('?', '\\?')}$`) });
const pager = (page: Page) => page.getByTestId('user-pager');

const userRow = (page: Page, user: Pick<Credentials, 'email'>) =>
  page.getByRole('row').filter({ has: page.getByRole('cell', { name: user.email, exact: true }) });

const editDialog = (page: Page) =>
  page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: 'Edit User' }) });
const editEmail = (page: Page) => editDialog(page).getByTestId('edit-user-email');
const editName = (page: Page) => editDialog(page).getByTestId('edit-user-name');
const enableSwitch = (page: Page) => editDialog(page).getByTestId('edit-user-enabled-switch');
const adminCheckbox = (page: Page) => editDialog(page).getByLabel('Full Site Administrator');
const commissionerCheckbox = (page: Page) => editDialog(page).getByLabel('Commissioner', { exact: true });
const saveButton = (page: Page) => editDialog(page).getByRole('button', { name: 'Save' });
const cancelEdit = (page: Page) => editDialog(page).getByRole('button', { name: 'Cancel' });

const deletePrompt = (page: Page, user: Pick<Credentials, 'name'>) =>
  page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: `Delete ${user.name}?` }) });

async function expectSwitch(toggle: Locator, state: 'on' | 'off') {
  await expect(toggle.locator(state === 'on' ? '.switch-on' : '.switch-off')).toHaveCount(1);
}

/** Registers one Commissioner for each name, in this order. The emails come from the names. */
async function seedUsers(
  setup: ApiSetup,
  admin: LoggedInUser,
  users: { name: string; email?: string; enabled?: boolean }[],
): Promise<SeedUser[]> {
  const seeded: SeedUser[] = [];

  for (const { name, email, enabled = true } of users) {
    const user = await setup.registerCommissioner({ name, email: email ?? emailFor({ name }) });

    if (!enabled) {
      await setup.setUserEnabled(admin, user, false);
    }
    seeded.push({ name: user.name, email: user.email, password: user.password, enabled });
  }

  return seeded;
}

/** Opens the route and shows only the users of the test. */
async function openUsersFor(page: Page, slug: string, count: number) {
  await page.goto('/admin/users');
  await emailSearch(page).fill(slug);
  await expectOnlyTagged(page, slug, count);
}

/** The table filters after a delay, so wait until each row belongs to the test. */
async function expectOnlyTagged(page: Page, slug: string, count: number) {
  await expect(userRows(page)).toHaveCount(count);
  await expect(userRows(page).filter({ hasNotText: slug })).toHaveCount(0);
}

async function openEditDialog(page: Page, user: Pick<Credentials, 'email'>) {
  await userRow(page, user).getByRole('button', { name: 'Edit' }).click();
  await expect(editName(page)).toBeVisible();
}

const EMAIL_COLUMN = 0;
const ENABLED_COLUMN = 2;

async function columnOnPage(page: Page, column: number) {
  return userRows(page).evaluateAll(
    (rows, index) => rows.map((row) => row.querySelectorAll('td')[index].textContent?.trim()),
    column,
  );
}

const emailsOnPage = (page: Page) => columnOnPage(page, EMAIL_COLUMN);
const enabledOnPage = (page: Page) => columnOnPage(page, ENABLED_COLUMN);

/** Logs in through the login screen, as a user who does not have a session yet. */
async function logInThroughScreen(page: Page, user: Pick<Credentials, 'email' | 'password'>) {
  await page.goto('/login');
  await emailField(page).fill(user.email);
  await page.getByTestId('login-password').fill(user.password);
  await page.getByRole('button', { name: /^Login/ }).click();
}

async function logInAsAdmin(context: BrowserContext, setup: ApiSetup) {
  return setup.logInNewAdmin(context, { name: unique('Admin') });
}

test('USERS-01 the table lists each user with Edit and Delete buttons', async ({ page, context, setup }) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [enabled, disabled] = await seedUsers(setup, admin, [
    { name: `${tag} Ann` },
    { name: `${tag} Bob`, enabled: false },
  ]);

  await openUsersFor(page, slug, 2);

  await expect(page.getByRole('heading', { name: 'Manage Users' })).toBeVisible();
  for (const column of ['Email', 'Name', 'Enabled?']) {
    await expect(header(page, column)).toBeVisible();
  }
  for (const [user, status] of [
    [enabled, 'Yes'],
    [disabled, 'No'],
  ] as const) {
    const row = userRow(page, user);

    await expect(row.getByRole('cell')).toHaveText([user.email, user.name, status, /Edit\s+Delete/]);
    await expect(row.getByRole('button', { name: 'Edit' })).toBeVisible();
    await expect(row.getByRole('button', { name: 'Delete' })).toBeVisible();
  }
});

test('USERS-02 the search boxes and the Enabled? filter keep matching users until "remove"', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-47');
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [ann, bob, cat] = await seedUsers(setup, admin, [
    { name: `${tag} Ann` },
    { name: `${tag} Bob`, enabled: false },
    { name: `${tag} Cat` },
  ]);
  await openUsersFor(page, slug, 3);

  await emailSearch(page).fill(`${slug}-bob`);
  await expect(userRows(page)).toHaveCount(1);
  await expect(userRow(page, bob)).toBeVisible();

  await emailSearch(page).fill(slug);
  await expect(userRows(page)).toHaveCount(3);
  await nameSearch(page).fill(`${tag} Cat`);
  await expect(userRows(page)).toHaveCount(1);
  await expect(userRow(page, cat)).toBeVisible();

  await nameSearch(page).fill('');
  await expect(userRows(page)).toHaveCount(3);
  await enabledFilter(page).getByRole('button').click();
  await enabledFilter(page).getByRole('link', { name: 'No', exact: true }).click();
  await expect(userRows(page)).toHaveCount(1);
  await expect(userRow(page, bob)).toBeVisible();

  await enabledFilter(page).getByRole('button').click();
  await enabledFilter(page).getByRole('link', { name: 'remove' }).click();
  await expect(userRows(page)).toHaveCount(3);
  await expect(userRow(page, ann)).toBeVisible();
});

test('USERS-03 each header sorts the rows and 15 rows show on each page', async ({ page, context, setup }) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const numbers = Array.from({ length: PAGE_SIZE + 1 }, (_, index) => String(index + 1).padStart(2, '0'));
  // The names run against the emails, and the first two users are Disabled
  const users = await seedUsers(
    setup,
    admin,
    numbers.map((number, index) => ({
      name: `${tag} ${String.fromCharCode('z'.charCodeAt(0) - index)}`,
      email: `${slug}-${number}@e2e.example`,
      enabled: index > 1,
    })),
  );
  await page.goto('/admin/users');
  await emailSearch(page).fill(slug);
  await expectOnlyTagged(page, slug, PAGE_SIZE);

  const byEmail = users.map((user) => user.email);

  await pager(page).getByText('2', { exact: true }).click();
  await expect(userRows(page)).toHaveCount(1);
  await expect.poll(async () => await emailsOnPage(page)).toEqual([byEmail[PAGE_SIZE]]);
  await pager(page).getByText('1', { exact: true }).click();
  await expect(userRows(page)).toHaveCount(PAGE_SIZE);

  const byName = [...users].reverse().map((user) => user.email);

  await header(page, 'Email').click();
  await expect.poll(async () => await emailsOnPage(page)).toEqual(byEmail.slice(0, PAGE_SIZE));
  await header(page, 'Email').click();
  await expect.poll(async () => await emailsOnPage(page)).toEqual(byEmail.slice(1).reverse());

  await header(page, 'Name').click();
  await expect.poll(async () => await emailsOnPage(page)).toEqual(byName.slice(0, PAGE_SIZE));
  await header(page, 'Name').click();
  await expect.poll(async () => await emailsOnPage(page)).toEqual(byName.slice(1).reverse());

  await header(page, 'Enabled?').click();
  await expect.poll(async () => (await enabledOnPage(page)).slice(0, 2)).toEqual(['No', 'No']);
  await header(page, 'Enabled?').click();
  await expect.poll(async () => (await enabledOnPage(page)).slice(-1)).toEqual(['No']);
});

test('USERS-04 Edit opens the dialog with the values of the user', async ({ page, context, setup }) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [disabled, enabled] = await seedUsers(setup, admin, [
    { name: `${tag} Ann`, enabled: false },
    { name: `${tag} Bob` },
  ]);
  await openUsersFor(page, slug, 2);

  await openEditDialog(page, enabled);

  await expect(editDialog(page).getByRole('heading', { name: 'Edit User' })).toBeVisible();
  await expect(editEmail(page)).toHaveValue(enabled.email);
  await expect(editName(page)).toHaveValue(enabled.name);
  await expectSwitch(enableSwitch(page), 'on');
  await expect(adminCheckbox(page)).not.toBeChecked();
  await expect(commissionerCheckbox(page)).toBeChecked();

  await cancelEdit(page).click();
  await openEditDialog(page, disabled);

  await expect(editEmail(page)).toHaveValue(disabled.email);
  await expectSwitch(enableSwitch(page), 'off');
});

test('USERS-05 Save closes the dialog and the table shows the new name', async ({ page, context, setup }) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  await openUsersFor(page, slug, 1);
  const newName = `${tag} Renamed`;
  const reload = page.waitForResponse((response) => response.url().endsWith(`/api/${USERS_PATH}`));

  await openEditDialog(page, user);
  await editName(page).fill(newName);
  await saveButton(page).click();

  await expect(editDialog(page)).toBeHidden();
  await reload;
  await expect(userRow(page, user).getByRole('cell', { name: newName, exact: true })).toBeVisible();
});

test('USERS-06 a Disabled user cannot log in until Enable is on again', async ({
  page,
  context,
  setup,
  newViewerPage,
}) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  await openUsersFor(page, slug, 1);

  await openEditDialog(page, user);
  await enableSwitch(page).click();
  await saveButton(page).click();
  await expect(userRow(page, user).getByRole('cell', { name: 'No', exact: true })).toBeVisible();

  const viewer = await newViewerPage();
  await logInThroughScreen(viewer, user);
  await expectToast(viewer, { title: 'Unable to Login', message: 'Invalid credentials.' });
  await expectLoginRoute(viewer);

  await openEditDialog(page, user);
  await enableSwitch(page).click();
  await saveButton(page).click();
  await expect(userRow(page, user).getByRole('cell', { name: 'Yes', exact: true })).toBeVisible();

  await logInThroughScreen(viewer, user);
  await expect(viewer).toHaveURL('/home');
});

test('USERS-07 a user with the Full Site Administrator role sees Admin Stuff', async ({
  page,
  context,
  setup,
  newViewerPage,
}) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  await openUsersFor(page, slug, 1);

  await openEditDialog(page, user);
  await adminCheckbox(page).check();
  await saveButton(page).click();
  await expect(editDialog(page)).toBeHidden();

  const viewer = await newViewerPage();
  await logInThroughScreen(viewer, user);
  await expect(viewer.getByRole('button', { name: 'Admin Stuff' })).toBeVisible();
});

test('USERS-08 an empty Email or Name sends no request and keeps the dialog open with a message', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-17');
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  await openUsersFor(page, slug, 1);
  await openEditDialog(page, user);
  const updates = recordApiRequests(page, { method: 'PUT', path: USER_PATH });

  await editEmail(page).fill('');
  await editName(page).focus();
  await expect(editDialog(page).getByText('Email is missing')).toBeVisible();
  await saveButton(page).click();

  await editEmail(page).fill(user.email);
  await editName(page).fill('');
  await editEmail(page).focus();
  await expect(editDialog(page).getByText('Name is missing')).toBeVisible();
  await saveButton(page).click();

  await expect(editDialog(page)).toBeVisible();
  expect(updates).toEqual([]);
});

test('USERS-09 Cancel closes the dialog, sends no request, and keeps the old name in the row', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-26');
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  await openUsersFor(page, slug, 1);
  await openEditDialog(page, user);
  const requests = recordApiRequests(page);

  await editName(page).fill(`${tag} Changed`);
  await cancelEdit(page).click();

  await expect(editDialog(page)).toBeHidden();
  expect(requests).toEqual([]);
  await expect(userRow(page, user).getByRole('cell', { name: user.name, exact: true })).toBeVisible();
});

test('USERS-10 Delete asks first, then removes the row of that user', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-26');
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [ann, bob, cat] = await seedUsers(setup, admin, [
    { name: `${tag} Ann` },
    { name: `${tag} Bob` },
    { name: `${tag} Cat` },
  ]);
  await openUsersFor(page, slug, 3);
  const deletes = recordApiRequests(page, { method: 'DELETE' });

  await userRow(page, cat).getByRole('button', { name: 'Delete' }).click();
  await expect(deletePrompt(page, cat)).toBeVisible();
  await deletePrompt(page, cat).getByRole('button', { name: 'Cancel' }).click();
  await expect(deletePrompt(page, cat)).toBeHidden();
  await expect(userRows(page)).toHaveCount(3);
  expect(deletes).toEqual([]);

  const release = await holdApiResponses(page, { method: 'DELETE', path: USER_PATH });
  await userRow(page, cat).getByRole('button', { name: 'Delete' }).click();
  await deletePrompt(page, cat).getByRole('button', { name: 'Yes, Delete User' }).click();
  await expect(workingModal(page)).toBeVisible();
  release();

  await expect(workingModal(page)).toBeHidden();
  await expectToast(page, { message: 'User deleted' });
  await expect(userRows(page)).toHaveCount(2);
  await expect(userRow(page, cat)).toBeHidden();
  await expect(userRow(page, ann)).toBeVisible();
  await expect(userRow(page, bob)).toBeVisible();
});

test('USERS-11 a Commissioner whom the Admin disabled gets the expired login toast at the next request', async ({
  page,
  context,
  setup,
  newViewerPage,
}) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [commissioner] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  const viewer = await newViewerPage();
  await setup.logInBrowser(viewer.context(), await setup.logIn(commissioner));
  await viewer.goto('/home');
  await expect(viewer.getByRole('button', { name: 'Log Out' })).toBeVisible();

  await openUsersFor(page, slug, 1);
  await openEditDialog(page, commissioner);
  await enableSwitch(page).click();
  await saveButton(page).click();
  await expect(userRow(page, commissioner).getByRole('cell', { name: 'No', exact: true })).toBeVisible();
  await viewer.getByRole('link', { name: commissioner.name }).click();

  await expectToast(viewer, { message: EXPIRED_SESSION_TOAST });
  await expectLoginRoute(viewer);
  await expect(viewer.getByRole('button', { name: 'Commissioner Login' })).toBeVisible();
});

test('USERS-12 an anonymous viewer goes to the login screen', async ({ page }) => {
  knownFailureOnAngularJs('W-03');

  await page.goto('/admin/users');

  await expectLoginRoute(page);
});

test('USERS-13 a Commissioner goes to the home screen', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-03');
  await setup.logInNewCommissioner(context);

  await page.goto('/admin/users');

  await expect(page).toHaveURL('/home');
});

test('USERS-14 an API error shows a toast for the users, the update, and the delete', async ({
  page,
  context,
  setup,
}) => {
  const admin = await logInAsAdmin(context, setup);
  const { tag, slug } = newTag();
  const [user] = await seedUsers(setup, admin, [{ name: `${tag} Ann` }]);
  const adminUser = await setup.registerAdmin({ name: `${tag} Boss` });

  const loadPage = await context.newPage();
  await failApiRequest(loadPage, { method: 'GET', path: USERS_PATH });
  await loadPage.goto('/admin/users');
  await expectToast(loadPage, { message: 'Unable to load users' });

  const updatePage = await context.newPage();
  await failApiRequest(updatePage, { method: 'PUT', path: USER_PATH });
  await openUsersFor(updatePage, slug, 2);
  await openEditDialog(updatePage, user);
  await editName(updatePage).fill(`${tag} Changed`);
  await saveButton(updatePage).click();
  await expectToast(updatePage, { message: 'Unable to update user' });
  await expect(editDialog(updatePage)).toBeHidden();

  // The API refuses to delete an Admin user
  await openUsersFor(page, slug, 2);
  await userRow(page, adminUser).getByRole('button', { name: 'Delete' }).click();
  await deletePrompt(page, adminUser).getByRole('button', { name: 'Yes, Delete User' }).click();
  await expectToast(page, { message: 'Unable to delete user' });
  await expect(workingModal(page)).toBeHidden();
  await expect(userRow(page, adminUser)).toBeVisible();
});
