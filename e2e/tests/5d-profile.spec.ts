import { BrowserContext, Page } from '@playwright/test';
import { ApiSetup, LoggedInUser } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { openDraftScreenLoggedIn } from '../helpers/draft-screen';
import { emailField, expectEmailValidationMessages, expectPasswordValidationMessages } from '../helpers/form';
import { failApiRequest } from '../helpers/intercept';
import { userLink } from '../helpers/navigation';
import { linkPath, readEmailVerificationKey } from '../helpers/mailpit';
import { expect, test } from '../helpers/test';
import { expectToast } from '../helpers/toast';

// Route group 5D of docs/flow-inventory.md. Each test name starts with the row ID.

const NEW_PASSWORD = 'brand-new-password';

function nameField(page: Page) {
  return page.getByTestId('profile-name');
}

function currentPasswordField(page: Page) {
  return page.getByTestId('profile-current-password');
}

function newPasswordField(page: Page) {
  return page.getByLabel('New Password', { exact: true });
}

function confirmNewPasswordField(page: Page) {
  return page.getByLabel('Confirm New Password', { exact: true });
}

function updateButton(page: Page) {
  return page.getByRole('button', { name: /Update User Profile/ });
}

function emailWarning(page: Page) {
  return page.getByTestId('email-change-warning');
}

async function openProfile(page: Page, user: LoggedInUser) {
  await page.goto('/profile');
  await expect(nameField(page)).toHaveValue(user.name);
}

async function openProfileLoggedIn(page: Page, context: BrowserContext, setup: ApiSetup) {
  const user = await setup.registerCommissioner();
  await setup.logInBrowser(context, user);
  await openProfile(page, user);

  return user;
}

async function expectProfileForm(page: Page, user: Pick<LoggedInUser, 'name' | 'email'>) {
  await expect(page.getByRole('heading', { name: 'Update User Profile' })).toBeVisible();
  await expect(emailField(page)).toHaveValue(user.email);
  await expect(nameField(page)).toHaveValue(user.name);
  await expect(currentPasswordField(page)).toHaveValue('');
  await expect(newPasswordField(page)).toHaveValue('');
  await expect(confirmNewPasswordField(page)).toHaveValue('');
  await expect(updateButton(page)).toBeDisabled();
}

async function expectMessage(page: Page, text: string) {
  await expect(page.getByText(text, { exact: true })).toBeVisible();
}

async function expectEmptyForm(page: Page) {
  await expect(page.getByRole('heading', { name: 'Update User Profile' })).toBeVisible();
  await expect(emailField(page)).toHaveValue('');
  await expect(nameField(page)).toHaveValue('');
}

test('PROFILE-01 the profile form shows the current values and a disabled button', async ({ page, context, setup }) => {
  const user = await openProfileLoggedIn(page, context, setup);

  await page.goto('/home');
  await userLink(page, user.name).click();

  await expect(page).toHaveURL('/profile');
  await expectProfileForm(page, user);
});

test('PROFILE-02 a new name is saved and the client goes to the last route that is not an auth route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const { commissioner, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);
  const newName = `${commissioner.name} Jr`;

  await userLink(page, commissioner.name).click();
  await nameField(page).fill(newName);
  await currentPasswordField(page).fill(commissioner.password);
  await updateButton(page).click();

  await expectToast(page, { message: 'User profile information updated!' });
  await expect(userLink(page, newName)).toBeVisible();
  await expect(page).toHaveURL(draftRoute);
});

test('PROFILE-03 a new password logs the viewer out and works at the next login', async ({ page, context, setup }) => {
  const user = await openProfileLoggedIn(page, context, setup);

  await currentPasswordField(page).fill(user.password);
  await newPasswordField(page).fill(NEW_PASSWORD);
  await confirmNewPasswordField(page).fill(NEW_PASSWORD);
  await updateButton(page).click();

  await expectToast(page, {
    message:
      'Since you changed your password, we had to log you out and log you back in again. But no biggie - just enter your new password now to login again.',
  });
  await expect(page).toHaveURL('/login');
  await expect(userLink(page, user.name)).toBeHidden();
  await setup.logIn({ ...user, password: NEW_PASSWORD });
});

test('PROFILE-04 a new email address shows the warning, and a change of letter case does not', async ({
  page,
  context,
  setup,
}) => {
  const user = await openProfileLoggedIn(page, context, setup);
  await expect(emailWarning(page)).toBeHidden();

  await emailField(page).fill(user.email.toUpperCase());
  await expect(emailWarning(page)).toBeHidden();

  await emailField(page).fill(`changed-${user.email}`);
  await expect(emailWarning(page)).toBeVisible();
  await expect(emailWarning(page)).toContainText(
    'Heads up: if you change to a new email address, you will be logged out.',
  );
});

test('PROFILE-05 a new email address logs the viewer out and sends the Email Verification Key link', async ({
  page,
  context,
  setup,
}) => {
  const user = await openProfileLoggedIn(page, context, setup);
  const newEmail = `changed-${user.email}`;

  await emailField(page).fill(newEmail);
  await expect(emailWarning(page)).toBeVisible();
  await currentPasswordField(page).fill(user.password);
  await updateButton(page).click();

  await expectToast(page, {
    message:
      'Your email was updated, so we logged you out. To log in again, click the link we emailed to your new address to verify it. See you soon!',
  });
  await expect(page).toHaveURL('/home');
  await expect(userLink(page, user.name)).toBeHidden();

  const { key, link } = await readEmailVerificationKey(newEmail);
  expect(decodeURIComponent(linkPath(link))).toBe(`/verify/${newEmail}/${key}`);

  await page.goto('/login');
  await emailField(page).fill(newEmail);
  await page.getByTestId('login-password').fill(user.password);
  await page.getByRole('button', { name: /^Login/ }).click();
  await expectToast(page, {
    title: 'Unable to Login',
    message: 'Your account email is still unverified. Please click the link in your email to continue.',
  });
});

test('PROFILE-06 the fields show their validation messages and the button is disabled', async ({
  page,
  context,
  setup,
}) => {
  await openProfileLoggedIn(page, context, setup);

  await emailField(page).fill('');
  await expectEmailValidationMessages(page);

  await nameField(page).fill('');
  await nameField(page).blur();
  await expectMessage(page, 'Name is missing');

  await expectPasswordValidationMessages(page, currentPasswordField(page));

  await newPasswordField(page).fill('1234567');
  await newPasswordField(page).blur();
  await expectMessage(page, 'New password is too short - 8 characters minimum');

  await confirmNewPasswordField(page).fill('another-value');
  await confirmNewPasswordField(page).blur();
  await expectMessage(page, 'New passwords do not match.');

  await expect(updateButton(page)).toBeDisabled();
});

test('PROFILE-07 a wrong current password gives a toast with the API messages and keeps the form', async ({
  page,
  context,
  setup,
}) => {
  const user = await openProfileLoggedIn(page, context, setup);

  await nameField(page).fill(`${user.name} Jr`);
  await currentPasswordField(page).fill('not-the-password');
  await updateButton(page).click();

  await expectToast(page, { title: 'Unable to update your user profile', message: 'Incorrect password entered.' });
  await expect(page).toHaveURL('/profile');
  await expect(nameField(page)).toHaveValue(`${user.name} Jr`);
});

test('PROFILE-08 an API error on the profile request gives a toast and an empty form', async ({
  page,
  context,
  setup,
}) => {
  const user = await setup.registerCommissioner();
  await setup.logInBrowser(context, user);
  await failApiRequest(page, { method: 'GET', path: 'commish/profile' });

  await page.goto('/profile');

  await expectToast(page, { message: 'Unable to load user profile' });
  await expectEmptyForm(page);
});

test('PROFILE-09 an anonymous viewer is redirected to the login screen', async ({ page }) => {
  knownFailureOnAngularJs('W-01');

  await page.goto('/profile');

  await expect(page).toHaveURL('/login');
});

test('PROFILE-10 an Admin sees the same form as a Commissioner', async ({ page, context, setup }) => {
  const admin = await setup.registerAdmin();
  await setup.logInBrowser(context, admin);

  await openProfile(page, admin);
  await expectProfileForm(page, admin);
});
