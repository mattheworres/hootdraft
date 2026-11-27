import { BrowserContext, Page } from '@playwright/test';
import { ApiSetup, Credentials } from '../helpers/api';
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
import { linkPath, readResetKey, withWrongKey } from '../helpers/mailpit';
import { completeRecaptcha, recaptchaCheckbox } from '../helpers/recaptcha';
import { expect, test } from '../helpers/test';
import { expectAlreadyLoggedInToast, expectToast, SERVER_ERROR_TOAST } from '../helpers/toast';

// Route group 5C of docs/flow-inventory.md. Each test name starts with the row ID.

const NEW_PASSWORD = 'brand-new-password';
const SAURON_TEXT = "Sauron was a bad guy. You don't want Sauron to steal your account, do you? We didn't think so.";

function resetPasswordButton(page: Page) {
  return page.getByRole('button', { name: /^Reset Password/ });
}

/** The Reset screen and the invite setup screen have the same form. They differ in the test ID prefix, heading, and button. */
interface PasswordScreen {
  prefix: 'reset' | 'invite';
  heading: string;
  submitLabel: string;
}

const RESET_SCREEN: PasswordScreen = { prefix: 'reset', heading: 'Reset Password', submitLabel: 'Reset Password' };
const INVITE_SCREEN: PasswordScreen = {
  prefix: 'invite',
  heading: 'Set Your Password',
  submitLabel: 'Set Your Password',
};

function passwordField(page: Page, screen: PasswordScreen) {
  return page.getByTestId(`${screen.prefix}-password`);
}

function confirmField(page: Page, screen: PasswordScreen) {
  return page.getByTestId(`${screen.prefix}-confirm-password`);
}

// The name ends with a non-breaking space
function submitButton(page: Page, screen: PasswordScreen) {
  return page.getByRole('button', { name: new RegExp(`^${screen.submitLabel}`) });
}

function badResetKeyHeading(page: Page) {
  return page.getByRole('heading', { name: 'Bad Reset Key' });
}

async function userWithResetKey(setup: ApiSetup) {
  const user = await setup.registerCommissioner();
  await setup.requestPasswordReset(user);

  return user;
}

async function invitedUser(setup: ApiSetup) {
  const inviter = await setup.registerCommissioner();

  return setup.inviteUser(inviter);
}

async function expectPasswordForm(page: Page, screen: PasswordScreen, email: string) {
  await expect(page.getByRole('heading', { name: screen.heading })).toBeVisible();
  await expect(page.getByTestId(`${screen.prefix}-email`)).toHaveText(email);
  await expect(passwordField(page, screen)).toBeVisible();
  await expect(confirmField(page, screen)).toBeVisible();
  await expect(page.getByTestId(`${screen.prefix}-show-password`)).toBeVisible();
  await expect(submitButton(page, screen)).toBeVisible();
}

async function submitNewPassword(page: Page, screen: PasswordScreen) {
  await passwordField(page, screen).fill(NEW_PASSWORD);
  await confirmField(page, screen).fill(NEW_PASSWORD);
  await submitButton(page, screen).click();
}

async function expectLoggedInAtHome(page: Page, user: Pick<Credentials, 'name'>) {
  await expect(page).toHaveURL('/home');
  await expect(userLink(page, user.name)).toBeVisible();
  await expectToast(page, { message: "Your password has been set and you've been logged in. Welcome!" });
}

async function expectBadResetKeyPanel(page: Page, screen: PasswordScreen, restartLinkName: RegExp) {
  await expect(badResetKeyHeading(page)).toBeVisible();
  await expect(page.getByRole('link', { name: restartLinkName })).toHaveAttribute('href', /lostPassword$/);
  await expect(passwordField(page, screen)).toBeHidden();
  await expect(page).not.toHaveURL('/home');
}

// Last and soft: AngularJS lacks this text (W-32), so the other checks and steps still run there
async function expectSauronText(page: Page) {
  await expect.soft(page.getByText(SAURON_TEXT)).toBeVisible();
}

async function emailedPath(email: string): Promise<string> {
  return linkPath((await readResetKey(email)).link);
}

async function openEmailedLink(page: Page, email: string): Promise<void> {
  await page.goto(await emailedPath(email));
}

async function expectPreviousRouteRedirect(page: Page, context: BrowserContext, setup: ApiSetup, path: string) {
  knownFailureOnAngularJs('W-06');
  const { commissioner, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);

  await page.goto(path);

  await expectAlreadyLoggedInToast(page, commissioner);
  await expect(page).toHaveURL(draftRoute);
}

async function expectSubmitApiError(page: Page, screen: PasswordScreen, title: string) {
  await failApiRequest(page, { method: 'POST', path: 'resetPassword', status: 500 });

  await submitNewPassword(page, screen);

  await expectToast(page, { title, message: SERVER_ERROR_TOAST });
  await expect(passwordField(page, screen)).toBeVisible();
}

async function expectValidationAndDisabledSubmit(page: Page, screen: PasswordScreen) {
  knownFailureOnAngularJs('W-18');
  const resetRequests = recordApiRequests(page, { path: 'resetPassword' });

  await expectPasswordValidationMessages(page, passwordField(page, screen));
  await expectConfirmValidationMessages(page, confirmField(page, screen));

  await expect(submitButton(page, screen)).toBeDisabled();
  await submitButton(page, screen).click({ force: true });
  expect(resetRequests).toEqual([]);
}

test('LOST-01 the lost password screen shows its heading, field, reCAPTCHA, and button', async ({ page }) => {
  await page.goto('/lostPassword');

  await expect(page.getByRole('heading', { name: 'Lost Password' })).toBeVisible();
  await expect(emailField(page)).toBeVisible();
  await expect(recaptchaCheckbox(page)).toBeVisible();
  await expect(resetPasswordButton(page)).toBeVisible();
});

test('LOST-02 a complete form sends the lost password email', async ({ page, setup }) => {
  const user = await setup.registerCommissioner();

  await page.goto('/lostPassword');
  await emailField(page).fill(user.email);
  await completeRecaptcha(page);
  await resetPasswordButton(page).click();

  await expect(page.getByRole('heading', { name: 'Reset Password Email Sent' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Lost Password' })).toBeHidden();
  await expect(page.getByText(`An email was sent to ${user.email}`)).toBeVisible();
  await expectToast(page, { message: 'Reset password email sent' });

  const { key, link } = await readResetKey(user.email);
  expect(decodeURIComponent(linkPath(link))).toBe(`/resetPassword/${user.email}/${key}`);
});

test('LOST-03 the Email field shows its validation messages', async ({ page }) => {
  await page.goto('/lostPassword');

  await expectEmailValidationMessages(page);
});

test('LOST-04 Reset Password is disabled until the reCAPTCHA is complete', async ({ page }) => {
  knownFailureOnAngularJs('W-18');
  const lostPasswordRequests = recordApiRequests(page, { path: 'lostPassword' });

  await page.goto('/lostPassword');
  await emailField(page).fill('someone@e2e.example');

  await expect(recaptchaCheckbox(page)).not.toBeChecked();
  await expect(resetPasswordButton(page)).toBeDisabled();
  await resetPasswordButton(page).click({ force: true });
  expect(lostPasswordRequests).toEqual([]);
});

test('LOST-05 a logged-in Commissioner who opens /lostPassword gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  await expectPreviousRouteRedirect(page, context, setup, '/lostPassword');
});

test('RESET-01 the Reset Key is checked, then the reset form shows', async ({ page, setup }) => {
  const user = await userWithResetKey(setup);
  const answerCheck = await holdApiResponses(page, { path: 'checkResetKey' });

  await openEmailedLink(page, user.email);

  await expect(page.getByText('Checking your Reset Key, one sec...')).toBeVisible();
  await expect(page.getByRole('heading', { name: RESET_SCREEN.heading })).toBeHidden();
  answerCheck();

  await expectPasswordForm(page, RESET_SCREEN, user.email);
  await expect(page.getByText('Checking your Reset Key, one sec...')).toBeHidden();
});

test('RESET-02 a new password logs the user in and works at the next login', async ({ page, setup }) => {
  const user = await userWithResetKey(setup);

  await openEmailedLink(page, user.email);
  await submitNewPassword(page, RESET_SCREEN);

  await expectLoggedInAtHome(page, user);
  await setup.logIn({ ...user, password: NEW_PASSWORD });
});

test('RESET-03 a wrong or used Reset Key shows the Bad Reset Key panel', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-32');
  const user = await userWithResetKey(setup);
  const { key, link } = await readResetKey(user.email);
  const expectPanel = async () => {
    await expectBadResetKeyPanel(page, RESET_SCREEN, /start the reset process over again/);
    await expectSauronText(page);
  };

  await test.step('a wrong key', async () => {
    await page.goto(withWrongKey(linkPath(link)));

    await expectPanel();
  });

  await test.step('a used key', async () => {
    await setup.resetPassword(user, key, NEW_PASSWORD);

    await page.goto(linkPath(link));

    await expectPanel();
  });
});

test('RESET-04 the fields show their validation messages and Reset Password is disabled', async ({ page, setup }) => {
  const user = await userWithResetKey(setup);
  await openEmailedLink(page, user.email);

  await expectValidationAndDisabledSubmit(page, RESET_SCREEN);
});

test('RESET-05 an API error on the reset request gives a toast and keeps the form', async ({ page, setup }) => {
  const user = await userWithResetKey(setup);
  await openEmailedLink(page, user.email);

  await expectSubmitApiError(page, RESET_SCREEN, 'Unable to Reset Password');
});

test('RESET-06 a logged-in Commissioner who opens the Reset link gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  const user = await userWithResetKey(setup);
  await expectPreviousRouteRedirect(page, context, setup, await emailedPath(user.email));
});

test('INVITE-01 the invite link shows the set password form', async ({ page, setup }) => {
  const invitee = await invitedUser(setup);

  await openEmailedLink(page, invitee.email);

  await expectPasswordForm(page, INVITE_SCREEN, invitee.email);
});

test('INVITE-02 a password logs the invited user in', async ({ page, setup }) => {
  const invitee = await invitedUser(setup);

  await openEmailedLink(page, invitee.email);
  await submitNewPassword(page, INVITE_SCREEN);

  await expectLoggedInAtHome(page, invitee);
});

test('INVITE-03 a wrong or used Reset Key shows the Bad Reset Key panel', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-32');
  const invitee = await invitedUser(setup);
  const { key, link } = await readResetKey(invitee.email);
  const expectPanel = async () => {
    await expectBadResetKeyPanel(page, INVITE_SCREEN, /request a password reset/);
    await expect(page.getByTestId('invite-bad-key-email')).toHaveText(invitee.email);
    await expect(page.getByRole('link', { name: 'reach out' })).toBeVisible();
    await expect(page.getByRole('link', { name: '@mattheworres' })).toBeVisible();
    await expectSauronText(page);
  };

  await test.step('a wrong key', async () => {
    await page.goto(withWrongKey(linkPath(link)));

    await expectPanel();
  });

  await test.step('a used key', async () => {
    await setup.resetPassword(invitee, key, NEW_PASSWORD);

    await page.goto(linkPath(link));

    await expectPanel();
  });
});

test('INVITE-04 the fields show their validation messages and Set Your Password is disabled', async ({
  page,
  setup,
}) => {
  const invitee = await invitedUser(setup);
  await openEmailedLink(page, invitee.email);

  await expectValidationAndDisabledSubmit(page, INVITE_SCREEN);
});

test('INVITE-05 an API error on the set password request gives a toast', async ({ page, setup }) => {
  const invitee = await invitedUser(setup);
  await openEmailedLink(page, invitee.email);

  await expectSubmitApiError(page, INVITE_SCREEN, 'Unable to Set Password');
});

test('INVITE-06 a logged-in Commissioner who opens the invite link gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  const invitee = await invitedUser(setup);
  await expectPreviousRouteRedirect(page, context, setup, await emailedPath(invitee.email));
});
