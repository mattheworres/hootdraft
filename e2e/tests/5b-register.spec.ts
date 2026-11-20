import { Page } from '@playwright/test';
import { Credentials, emailFor, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { openDraftScreenLoggedIn } from '../helpers/draft-screen';
import {
  emailField,
  expectConfirmValidationMessages,
  expectEmailValidationMessages,
  expectPasswordValidationMessages,
} from '../helpers/form';
import { failApiRequest, holdApiResponses, recordApiRequests } from '../helpers/intercept';
import { userLink } from '../helpers/navigation';
import { linkPath, readEmailVerificationKey, withWrongKey } from '../helpers/mailpit';
import { completeRecaptcha, recaptchaCheckbox } from '../helpers/recaptcha';
import { expect, test } from '../helpers/test';
import { expectAlreadyLoggedInToast, expectToast } from '../helpers/toast';

// Route group 5B of docs/flow-inventory.md. Each test name starts with the row ID.

function nameField(page: Page) {
  return page.getByTestId('register-name');
}

function passwordField(page: Page) {
  return page.getByTestId('register-password');
}

function confirmPasswordField(page: Page) {
  return page.getByTestId('register-confirm-password');
}

// The name ends with a non-breaking space
function registerButton(page: Page) {
  return page.getByRole('button', { name: /^Register/ });
}

function workingModal(page: Page) {
  return page.getByRole('dialog').filter({ has: page.getByRole('progressbar') });
}

function newCredentials(): Credentials {
  const name = unique('Registrant');

  return { name, email: emailFor({ name }), password: 'registrant-password' };
}

async function fillRegistrationForm(page: Page, credentials: Credentials) {
  await emailField(page).fill(credentials.email);
  await nameField(page).fill(credentials.name);
  await passwordField(page).fill(credentials.password);
  await confirmPasswordField(page).fill(credentials.password);
  await completeRecaptcha(page);
}

/** Opens the path of the emailed link in the client under test, which can differ from the host in the email. */
async function openEmailVerificationLink(page: Page, email: string) {
  const { link } = await readEmailVerificationKey(email);

  await page.goto(linkPath(link));
}

async function expectEmailVerificationFailure(page: Page) {
  await expect(page.getByRole('heading', { name: 'Unable to Verify Your Email' })).toBeVisible();
  await expect(page.getByText('Unable to verify your email', { exact: true })).toBeVisible();
}

test('REG-01 the register screen shows its heading, fields, and a disabled Register', async ({ page }) => {
  await page.goto('/register');

  await expect(page.getByRole('heading', { name: 'Commissioner Registration' })).toBeVisible();
  await expect(emailField(page)).toBeVisible();
  await expect(nameField(page)).toBeVisible();
  await expect(passwordField(page)).toBeVisible();
  await expect(confirmPasswordField(page)).toBeVisible();
  await expect(page.getByTestId('register-show-password')).toBeVisible();
  await expect(page.getByText('Human?')).toBeVisible();
  await expect(recaptchaCheckbox(page)).toBeVisible();
  await expect(registerButton(page)).toBeDisabled();
});

test('REG-02 a complete form registers the user and sends the email link', async ({ page }) => {
  const credentials = newCredentials();
  const answerRegister = await holdApiResponses(page, { path: 'register' });

  await page.goto('/register');
  await fillRegistrationForm(page, credentials);
  await registerButton(page).click();

  await expect(workingModal(page)).toBeVisible();
  answerRegister();
  await expect(workingModal(page)).toBeHidden();

  await expect(page.getByRole('heading', { name: 'Registration Almost Complete!' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Commissioner Registration' })).toBeHidden();
  await expect(page.getByText(credentials.name, { exact: true })).toBeVisible();
  await expect(page.getByText(credentials.email, { exact: true })).toBeVisible();
  await expectToast(page, { message: 'We sent you an email to verify your address' });

  const { key, link } = await readEmailVerificationKey(credentials.email);
  expect(decodeURIComponent(linkPath(link))).toBe(`/verify/${credentials.email}/${key}`);
});

test('REG-03 the Email field shows its validation messages', async ({ page }) => {
  await page.goto('/register');

  await expectEmailValidationMessages(page);
});

test('REG-04 the Name field shows its validation message', async ({ page }) => {
  await page.goto('/register');

  await nameField(page).focus();
  await nameField(page).blur();

  await expect(page.getByText('Name is missing')).toBeVisible();
});

test('REG-05 the Password field shows its validation messages', async ({ page }) => {
  await page.goto('/register');

  await expectPasswordValidationMessages(page, passwordField(page));
});

test('REG-06 the Confirm field shows its validation messages', async ({ page }) => {
  await page.goto('/register');
  await passwordField(page).fill('first-password');

  await expectConfirmValidationMessages(page, confirmPasswordField(page));
});

test('REG-07 an email or name in use gives the API messages and resets the reCAPTCHA', async ({ page, setup }) => {
  const existing = await setup.registerCommissioner();

  await page.goto('/register');
  await fillRegistrationForm(page, { ...newCredentials(), email: existing.email, name: existing.name });
  await registerButton(page).click();

  await expectToast(page, { title: 'Unable to register', message: 'Name already taken. Email already registered.' });
  await expect(recaptchaCheckbox(page)).not.toBeChecked();
  await expect(page.getByRole('heading', { name: 'Commissioner Registration' })).toBeVisible();
  await expect(emailField(page)).toHaveValue(existing.email);
  await expect(registerButton(page)).toBeDisabled();
});

test('REG-08 an API error on the register request gives a toast with the status', async ({ page }) => {
  await failApiRequest(page, { method: 'POST', path: 'register', status: 500 });

  await page.goto('/register');
  await fillRegistrationForm(page, newCredentials());
  await registerButton(page).click();

  await expectToast(page, {
    title: 'Unable to register',
    message: "Whoops! We hit a snag - looks like it's on our end (500)",
  });
});

test('REG-09 a logged-in Commissioner who opens /register gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const { commissioner, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);

  await page.goto('/register');

  await expectAlreadyLoggedInToast(page, commissioner);
  await expect(page).toHaveURL(draftRoute);
});

test('VERIFY-01 the email link verifies the email and logs the user in', async ({ page, setup }) => {
  const user = await setup.registerEmailUnverifiedUser();
  await page.clock.install();

  const emailVerificationResponse = page.waitForResponse((response) =>
    response.url().endsWith('/api/emailVerification'),
  );
  await openEmailVerificationLink(page, user.email);
  expect((await emailVerificationResponse).ok()).toBe(true);

  await expect(workingModal(page)).toBeVisible();
  await page.clock.runFor(3_000);
  await expect(workingModal(page)).toBeVisible();
  await page.clock.runFor(500);

  await expect(workingModal(page)).toBeHidden();
  await expect(page).toHaveURL('/home');
  await expect(userLink(page, user.name)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();
  await expectToast(page, { message: 'Your email is now verified, and you are now logged in. Welcome!' });
});

test('VERIFY-02 a wrong or used Email Verification Key shows the error panel', async ({ page, setup }) => {
  await test.step('a wrong key', async () => {
    const user = await setup.registerEmailUnverifiedUser();
    const { link } = await readEmailVerificationKey(user.email);

    await page.goto(withWrongKey(linkPath(link)));

    await expectEmailVerificationFailure(page);
    await expect(page).not.toHaveURL('/home');
  });

  await test.step('a used key', async () => {
    const user = await setup.registerCommissioner();

    await openEmailVerificationLink(page, user.email);

    await expectEmailVerificationFailure(page);
    await expect(page).not.toHaveURL('/home');
  });
});

test('VERIFY-03 /verify with no email and no key shows the error panel', async ({ page }) => {
  await page.goto('/verify');

  await expectEmailVerificationFailure(page);
});

test('VERIFY-04 a logged-in Commissioner who opens the email link gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const user = await setup.registerEmailUnverifiedUser();
  const { commissioner, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);
  const emailVerificationRequests = recordApiRequests(page, { path: 'emailVerification' });

  await openEmailVerificationLink(page, user.email);

  await expectAlreadyLoggedInToast(page, commissioner);
  expect(emailVerificationRequests).toEqual([]);
  await expect(page).toHaveURL(draftRoute);
});
