import { expect, Page } from '@playwright/test';
import { Credentials } from './api';

/**
 * Asserts that a toast with this message shows, and with this title if the row names one.
 * The AngularJS toast comes from a library template with no role and no `data-testid`, so the visible text is
 * the only handle that both clients share.
 */
export async function expectToast(page: Page, toast: { message: string | RegExp; title?: string }): Promise<void> {
  if (toast.title !== undefined) {
    await expect(page.getByText(toast.title, { exact: true })).toBeVisible();
  }

  await expect(page.getByText(toast.message, { exact: true })).toBeVisible();
}

/**
 * Asserts that exactly one toast with this message shows once the page is quiet. `expectToast` passes as soon as the
 * first toast shows, so it cannot see a second one that arrives a moment later.
 */
export async function expectSingleToast(page: Page, message: string): Promise<void> {
  await page.waitForLoadState('networkidle');
  await expect(page.getByText(message, { exact: true })).toHaveCount(1);
}

/** The toast for a logged-in viewer who opens an auth route: LOGIN-13, REG-09, VERIFY-04. */
export async function expectAlreadyLoggedInToast(page: Page, user: Pick<Credentials, 'name'>): Promise<void> {
  await expectToast(page, { title: 'Logged In', message: `Already logged in as ${user.name}.` });
}
