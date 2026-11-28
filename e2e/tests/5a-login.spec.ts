import { BrowserContext, Page } from '@playwright/test';
import { ApiSetup, Credentials, emailFor, unique } from '../helpers/api';
import {
  emailField,
  expectEmailValidationMessages,
  expectPasswordValidationMessages,
  workingModal,
} from '../helpers/form';
import { footerVersionText, knownFailureOnAngularJs } from '../helpers/client';
import { boardRoute } from '../helpers/draft-board';
import { openDraftScreen, openDraftScreenLoggedIn } from '../helpers/draft-screen';
import { failApiRequest, recordApiRequests } from '../helpers/intercept';
import { expectFooterHidden, expectLoginRoute, logoLink, userLink } from '../helpers/navigation';
import { readEmailVerificationKey, readResetKey } from '../helpers/mailpit';
import { expect, test } from '../helpers/test';
import { setEmailVerificationKeyTime } from '../helpers/timestamps';
import { expectAlreadyLoggedInToast, expectToast, EXPIRED_SESSION_TOAST, SERVER_ERROR_TOAST } from '../helpers/toast';

// Route group 5A of docs/flow-inventory.md. Each test name starts with the row ID.

function passwordField(page: Page) {
  return page.getByTestId('login-password');
}

// The name ends with a non-breaking space, and the navigation has a "Commissioner Login" button
function loginButton(page: Page) {
  return page.getByRole('button', { name: /^Login/ });
}

async function submitLoginForm(page: Page, credentials: Pick<Credentials, 'email' | 'password'>) {
  await emailField(page).fill(credentials.email);
  await passwordField(page).fill(credentials.password);
  await loginButton(page).click();
}

/** Asserts the navigation of an anonymous viewer: NAV-01. */
async function expectAnonymousNavigation(page: Page) {
  await expect(logoLink(page)).toHaveAttribute('href', /^\/?home$/);
  await expect(page.getByRole('button', { name: 'Commissioner Login' })).toHaveAttribute('href', /^\/?login$/);
  await expect(page.getByRole('link', { name: 'New League' })).toBeHidden();
  await expect(page.getByRole('link', { name: 'Invite A Friend' })).toBeHidden();
  await expect(page.getByRole('button', { name: 'Admin Stuff' })).toBeHidden();
}

async function openInviteDialog(page: Page, context: BrowserContext, setup: ApiSetup) {
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');
  await page.getByRole('link', { name: 'Invite A Friend' }).click();

  return { commissioner, dialog: page.getByRole('dialog') };
}

test('LOGIN-01 the login screen shows its heading, fields, and links', async ({ page }) => {
  knownFailureOnAngularJs('W-35');

  await page.goto('/login');

  await expect(page.getByRole('heading', { name: 'Commissioner Login' })).toBeVisible();
  await expect(emailField(page)).toBeVisible();
  await expect(passwordField(page)).toBeVisible();
  await expect(page.getByTestId('login-show-password')).toBeVisible();
  await expect(loginButton(page)).toBeVisible();

  await page.getByRole('link', { name: 'Lost password?' }).click();
  await expect(page).toHaveURL('/lostPassword');
  await page.goBack();
  await page.getByRole('button', { name: 'Create a new account!' }).click();
  await expect(page).toHaveURL('/register');
});

test('LOGIN-02 an Enabled, Email Verified user logs in from /login', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  // Hold the login answer, so the working modal stays open long enough to see
  let answerLogin = () => {};
  const loginAnswered = new Promise<void>((resolve) => (answerLogin = resolve));
  await page.route('**/api/login', async (route) => {
    const response = await route.fetch();
    await loginAnswered;
    await route.fulfill({ response });
  });

  await page.goto('/login');
  await submitLoginForm(page, commissioner);

  await expect(workingModal(page)).toBeVisible();
  answerLogin();
  await expect(workingModal(page)).toBeHidden();

  await expectToast(page, { title: 'Logged In', message: `Welcome back, ${commissioner.name}!` });
  await expect(page).toHaveURL('/home');
  await expect(userLink(page, commissioner.name)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();
});

test('LOGIN-03 a login after a Draft screen goes to the last route that is not an auth route', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  const draftRoute = await openDraftScreen(page, draft);
  await page.getByRole('button', { name: 'Commissioner Login' }).click();
  await submitLoginForm(page, commissioner);

  await expect(page).toHaveURL(draftRoute);
});

test('LOGIN-04 a Commissioner who browsed their Draft without a login returns to it after the login', async ({
  page,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await page.goto(boardRoute(draft));
  const draftRoute = await openDraftScreen(page, draft);
  await expect(page.getByRole('link', { name: 'Enter Pick' })).toBeHidden();
  await page.getByRole('button', { name: 'Commissioner Login' }).click();
  await submitLoginForm(page, commissioner);

  await expect(page).toHaveURL(draftRoute);
  await expect(page.getByRole('link', { name: 'Enter Pick' })).toBeVisible();
});

test('LOGIN-05 a wrong password gives the invalid credentials toast', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();

  await page.goto('/login');
  await submitLoginForm(page, { email: commissioner.email, password: 'not-the-password' });

  await expectToast(page, { title: 'Unable to Login', message: 'Invalid credentials.' });
  await expectLoginRoute(page);
});

test('LOGIN-06 a Disabled user gets the invalid credentials toast', async ({ page, setup }) => {
  const admin = await setup.registerAdmin();
  const commissioner = await setup.registerCommissioner();
  await setup.setUserEnabled(admin, commissioner, false);

  await page.goto('/login');
  await submitLoginForm(page, commissioner);

  await expectToast(page, { title: 'Unable to Login', message: 'Invalid credentials.' });
  await expectLoginRoute(page);
});

test('LOGIN-07 an Email Unverified user with a valid Email Verification Key is told to use the link', async ({
  page,
  setup,
}) => {
  const user = await setup.registerEmailUnverifiedUser();

  await page.goto('/login');
  await submitLoginForm(page, user);

  await expectToast(page, {
    title: 'Unable to Login',
    message: 'Your account email is still unverified. Please click the link in your email to continue.',
  });
  await expectLoginRoute(page);
});

test('LOGIN-08 an Email Unverified user with an expired Email Verification Key gets a new email', async ({
  page,
  setup,
}) => {
  const user = await setup.registerEmailUnverifiedUser();
  const { key: expiredKey } = await readEmailVerificationKey(user.email);
  await setEmailVerificationKeyTime(user.email, { hours: 49 });

  await page.goto('/login');
  await submitLoginForm(page, user);

  await expectToast(page, {
    title: 'Unable to Login',
    message:
      'Your account email is still unverified. Your link expired, so we sent a new one. Please click the link in your email to continue.',
  });
  await expect.poll(async () => (await readEmailVerificationKey(user.email)).key).not.toBe(expiredKey);
});

test('LOGIN-09 the Email field shows its validation messages', async ({ page }) => {
  await page.goto('/login');

  await expectEmailValidationMessages(page);
});

test('LOGIN-10 the Password field shows its validation messages', async ({ page }) => {
  await page.goto('/login');

  await expectPasswordValidationMessages(page, passwordField(page));
});

test('LOGIN-11 the Show switch turns the password into plain text and back', async ({ page }) => {
  await page.goto('/login');
  await passwordField(page).fill('a-secret-password');
  await expect(passwordField(page)).toHaveAttribute('type', 'password');

  await page.getByTestId('login-show-password').click();
  await expect(passwordField(page)).toHaveAttribute('type', 'text');
  await expect(passwordField(page)).toHaveValue('a-secret-password');

  await page.getByTestId('login-show-password').click();
  await expect(passwordField(page)).toHaveAttribute('type', 'password');
});

test('LOGIN-12 selecting Login with an invalid form sends no request', async ({ page }) => {
  const loginRequests = recordApiRequests(page, { path: 'login' });

  await page.goto('/login');
  await loginButton(page).click();

  await expect(workingModal(page)).toBeHidden();
  expect(loginRequests).toEqual([]);
});

test('LOGIN-13 a logged-in Commissioner who opens /login gets a toast and goes to the previous route', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-06');
  const { commissioner, draftRoute } = await openDraftScreenLoggedIn(page, context, setup);

  await page.goto('/login');

  await expectAlreadyLoggedInToast(page, commissioner);
  await expect(page).toHaveURL(draftRoute);
});

test('LOGIN-14 an API error on the login request gives a toast with the status', async ({ page, setup }) => {
  const commissioner = await setup.registerCommissioner();
  await failApiRequest(page, { method: 'POST', path: 'login', status: 500 });

  await page.goto('/login');
  await submitLoginForm(page, commissioner);

  await expectToast(page, {
    title: 'Unable to Login',
    message: SERVER_ERROR_TOAST,
  });
});

test('NAV-01 an anonymous viewer sees the logo and the login button', async ({ page }) => {
  await page.goto('/home');

  await expectAnonymousNavigation(page);
});

test('NAV-02 a Commissioner sees the Commissioner navigation without Admin Stuff', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);

  await page.goto('/home');

  await expect(page.getByRole('link', { name: 'New League' })).toHaveAttribute('href', /^\/?commish\/draft\/create$/);
  await expect(page.getByRole('link', { name: 'Invite A Friend' })).toBeVisible();
  await expect(userLink(page, commissioner.name)).toHaveAttribute('href', /^\/?profile$/);
  await expect(page.getByText(`${commissioner.name} logged in`)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Admin Stuff' })).toBeHidden();
});

test('NAV-03 the Admin Stuff menu links to the Admin screens', async ({ page, context, setup }) => {
  const admin = await setup.logInNewAdmin(context);

  await page.goto('/home');
  await page.getByRole('button', { name: 'Admin Stuff' }).click();

  await expect(page.getByRole('link', { name: 'Users' })).toHaveAttribute('href', /^\/?admin\/users$/);
  await expect(page.getByRole('link', { name: 'Player Data' })).toHaveAttribute('href', /^\/?admin\/proplayers$/);
  await expect(page.getByRole('link', { name: 'Draft Stats' })).toHaveAttribute('href', /^\/?admin\/stats$/);
});

test('NAV-04 Log Out ends the login and goes to /home', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');

  await page.getByRole('button', { name: 'Log Out' }).click();

  await expectToast(page, { message: 'Logged Out' });
  await expect(page).toHaveURL('/home');
  await expectAnonymousNavigation(page);

  await page.reload();
  await expectAnonymousNavigation(page);
});

test('NAV-05 a new tab shows the logged-in navigation', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();

  const newTab = await context.newPage();
  await newTab.goto('/home');

  await expect(userLink(newTab, commissioner.name)).toBeVisible();
  await expect(newTab.getByRole('button', { name: 'Log Out' })).toBeVisible();
});

test('NAV-06 a Commissioner whom an Admin disabled gets the expired login toast', async ({
  browser,
  baseURL,
  page,
  context,
  setup,
}) => {
  const admin = await setup.registerAdmin();
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();

  const adminContext = await browser.newContext({ baseURL });
  try {
    await setup.logInBrowser(adminContext, admin);
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/users');
    await adminPage.getByRole('textbox', { name: 'Search emails...' }).fill(commissioner.email);
    const userRow = adminPage
      .getByRole('row')
      .filter({ has: adminPage.getByRole('cell', { name: commissioner.email, exact: true }) });
    await userRow.getByRole('button', { name: 'Edit' }).click();
    const editUserDialog = adminPage.getByRole('dialog');
    await editUserDialog.getByRole('radio').click();
    await editUserDialog.getByRole('button', { name: 'Save' }).click();
    await expect(userRow).toContainText('No');
  } finally {
    await adminContext.close();
  }

  await userLink(page, commissioner.name).click();

  await expectToast(page, { message: EXPIRED_SESSION_TOAST });
  await expectLoginRoute(page);
  await expectAnonymousNavigation(page);
});

test('NAV-07 a Commissioner whose session ended on the server gets the expired login toast after a reload', async ({
  page,
  context,
  setup,
}) => {
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();

  await commissioner.api.post('logout');
  await page.reload();

  await expectToast(page, { message: EXPIRED_SESSION_TOAST });
  await expectLoginRoute(page);
  await expectAnonymousNavigation(page);
});

test('NAV-08 the footer shows the version, the license, and the copyright, and hides on the board', async ({
  page,
  setup,
}) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, {
    status: 'In Progress',
    depthChartPositions: [{ position: 'QB', slots: 1 }],
  });

  await page.goto('/home');

  await expect(page.getByRole('link', { name: footerVersionText })).toBeVisible();
  await expect(page.getByRole('link', { name: 'GNU GPL3 License' })).toBeVisible();
  await expect(page.getByText(`2010 – ${new Date().getFullYear()} by Matthew Orres`)).toBeVisible();

  // The Draft navigation shows once the Draft has loaded, so the footer check does not pass before the screen renders
  await page.goto(boardRoute(draft));
  await expect(page.getByRole('link', { name: draft.name })).toBeVisible();
  await expectFooterHidden(page);

  await page.goto(`/draft/${draft.id}/depth_chart`);
  await expect(page.getByRole('link', { name: draft.name })).toBeVisible();
  await expectFooterHidden(page);
});

test('NAV-09 the menu button opens the menu at phone width and each link closes it', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-36');
  const commissioner = await setup.logInNewCommissioner(context);
  await page.setViewportSize({ width: 400, height: 800 });
  // Log Out comes last: it ends the login. Each link starts on a route that the link changes.
  const links = [
    { start: '/home', link: page.getByRole('link', { name: 'New League' }), goesTo: '/commish/draft/create' },
    { start: '/home', link: userLink(page, commissioner.name), goesTo: '/profile' },
    { start: '/profile', link: logoLink(page), goesTo: '/home' },
    { start: '/profile', link: page.getByRole('button', { name: 'Log Out' }), goesTo: '/home' },
  ];
  const menuLinks = page.getByRole('button', { name: /^(Log Out|Commissioner Login)/ });

  for (const { start, link, goesTo } of links) {
    await page.goto(start);
    await expect(menuLinks).toBeHidden();

    await page.getByRole('button', { name: 'Toggle navigation' }).click();
    await expect(menuLinks).toBeVisible();

    await link.click();

    await expect(page).toHaveURL(goesTo);
    await expect(menuLinks).toBeHidden();
  }
});

test('NAV-10 an unknown URL goes to /home', async ({ page }) => {
  knownFailureOnAngularJs('W-05');

  await page.goto('/nothing-here');

  await expect(page).toHaveURL('/home');
});

test('INV-01 Invite A Friend opens the dialog with a disabled Invite button', async ({ page, context, setup }) => {
  const commissioner = await setup.logInNewCommissioner(context);
  await page.goto('/home');

  await page.getByRole('link', { name: 'Invite A Friend' }).click();

  const dialog = page.getByRole('dialog');
  await expect(dialog.getByRole('heading', { name: 'Invite New User' })).toBeVisible();
  await expect(dialog.getByRole('textbox', { name: 'Email' })).toBeVisible();
  await expect(dialog.getByTestId('invite-name')).toBeVisible();
  await expect(dialog.getByTestId('invite-message')).toBeVisible();
  await expect(dialog.getByRole('button', { name: 'Invite' })).toBeDisabled();
});

test('INV-02 inviting a new user shows a toast, closes the dialog, and sends the invite email', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, dialog } = await openInviteDialog(page, context, setup);
  const name = unique('Friend');
  const invitee = { name, email: emailFor({ name }) };

  await dialog.getByRole('textbox', { name: 'Email' }).fill(invitee.email);
  await dialog.getByTestId('invite-name').fill(invitee.name);
  await dialog.getByRole('button', { name: 'Invite' }).click();

  await expectToast(page, { message: `${invitee.name} should get an email shortly inviting them to join. Good job!` });
  await expect(dialog).toBeHidden();
  const { link } = await readResetKey(invitee.email);
  expect(link).toContain('/inviteSetup/');
});

test('INV-03 the invite dialog shows its validation messages and keeps Invite disabled', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, dialog } = await openInviteDialog(page, context, setup);
  const email = dialog.getByRole('textbox', { name: 'Email' });

  await email.focus();
  await email.blur();
  await expect(dialog.getByText('Email is missing')).toBeVisible();

  await email.fill('abc');
  await email.blur();
  await expect(dialog.getByText('Email is invalid')).toBeVisible();

  await dialog.getByTestId('invite-name').focus();
  await dialog.getByTestId('invite-name').blur();
  await expect(dialog.getByText('Name is missing')).toBeVisible();
  await expect(dialog.getByRole('button', { name: 'Invite' })).toBeDisabled();
});

test('INV-04 inviting an existing email shows the API message and keeps the dialog open', async ({
  page,
  context,
  setup,
}) => {
  const { commissioner, dialog } = await openInviteDialog(page, context, setup);

  await dialog.getByRole('textbox', { name: 'Email' }).fill(commissioner.email);
  await dialog.getByTestId('invite-name').fill(unique('Friend'));
  await dialog.getByRole('button', { name: 'Invite' }).click();

  await expectToast(page, { message: 'Unable to invite user: Email already registered.' });
  await expect(dialog).toBeVisible();
});

test('INV-05 Cancel closes the invite dialog and sends no request', async ({ page, context, setup }) => {
  const { commissioner, dialog } = await openInviteDialog(page, context, setup);
  const inviteRequests = recordApiRequests(page, { method: 'POST' });

  await dialog.getByRole('button', { name: 'Cancel' }).click();

  await expect(dialog).toBeHidden();
  expect(inviteRequests).toEqual([]);
});
