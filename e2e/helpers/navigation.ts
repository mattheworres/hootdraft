import { Page } from '@playwright/test';

/** The link in the navigation that shows the logged-in user's name and opens the profile. */
export function userLink(page: Page, name: string) {
  return page.getByRole('link', { name });
}
