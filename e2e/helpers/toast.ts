import { expect, Page } from '@playwright/test';
import { Credentials } from './api';
import { expectLoginRoute } from './navigation';

/** The message of the toast for an API answer with status 500. */
export const SERVER_ERROR_TOAST = "Whoops! We hit a snag - looks like it's on our end (500)";

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

/** The message of the shared toast for a session that ended on the server. */
export const EXPIRED_SESSION_TOAST = 'Your login has expired. You must log back in if you wish to continue.';

/**
 * Checks the result of a save after the session ended on the server: the login screen and the one shared toast,
 * without the local toast of W-33. Covers EDITPICK-07 and ADDTRADE-08.
 */
export async function expectExpiredSessionLogout(page: Page): Promise<void> {
  await expectLoginRoute(page);
  await expectToast(page, { message: EXPIRED_SESSION_TOAST });
  await expectSingleToast(page, EXPIRED_SESSION_TOAST);
  await expect(page.getByText('Unauthorized: please log in.')).toBeHidden();
}

/** The toast for a logged-in viewer who opens an auth route: LOGIN-13, REG-09, VERIFY-04. */
export async function expectAlreadyLoggedInToast(page: Page, user: Pick<Credentials, 'name'>): Promise<void> {
  await expectToast(page, { title: 'Logged In', message: `Already logged in as ${user.name}.` });
}
