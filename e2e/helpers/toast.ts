import { expect, Page } from '@playwright/test';

/**
 * Asserts that a toast with this message shows, and with this title if the row names one.
 * The AngularJS toast comes from a library template with no role and no `data-testid`, so the visible text is
 * the only handle that both clients share.
 */
export async function expectToast(page: Page, toast: { message: string; title?: string }): Promise<void> {
  if (toast.title !== undefined) {
    await expect(page.getByText(toast.title, { exact: true })).toBeVisible();
  }

  await expect(page.getByText(toast.message, { exact: true })).toBeVisible();
}
