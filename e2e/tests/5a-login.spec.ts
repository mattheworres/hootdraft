import { expect, test } from '../helpers/test';
import { expectToast } from '../helpers/toast';

// Route group 5A of docs/flow-inventory.md. Each test name starts with the row ID.

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
  await page.getByRole('textbox', { name: 'Email' }).fill(commissioner.email);
  await page.getByTestId('login-password').fill(commissioner.password);
  // The name ends with a non-breaking space, and the navigation has a "Commissioner Login" button
  await page.getByRole('button', { name: /^Login/ }).click();

  const workingModal = page.getByRole('dialog').filter({ has: page.getByRole('progressbar') });
  await expect(workingModal).toBeVisible();
  answerLogin();
  await expect(workingModal).toBeHidden();

  await expectToast(page, { title: 'Logged In', message: `Welcome back, ${commissioner.name}!` });
  await expect(page).toHaveURL('/home');
  await expect(page.getByRole('link', { name: commissioner.name })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();
});
