import { Locator, Page } from '@playwright/test';
import { expect } from './test';

/** The link in the navigation that shows the logged-in user's name and opens the profile. */
export function userLink(page: Page, name: string) {
  return page.getByRole('link', { name });
}

/** The logo link of the top navigation bar. It opens /home. */
export function logoLink(page: Page) {
  return page.getByRole('link', { name: 'Hoot Draft' });
}

/** Checks that the footer is hidden. Call it once the screen has loaded, or the check passes on an empty page. */
export async function expectFooterHidden(page: Page): Promise<void> {
  await expect(page.getByRole('link', { name: 'GNU GPL3 License' })).toBeHidden();
}

export interface MenuRoute {
  /** The name of the link in the menu. */
  link: string;
  url: RegExp;
}

/**
 * Selects each link of an open menu in turn and checks the route. The menu must be open when the call starts.
 * `reopen` opens it again after each link, because a link closes it.
 */
export async function expectMenuLinksOpenRoutes(
  page: Page,
  menu: { linkByName: (name: string) => Locator; reopen: () => Promise<void> },
  routes: MenuRoute[],
): Promise<void> {
  for (const { link, url } of routes) {
    await menu.linkByName(link).click();
    await expect(page).toHaveURL(url);
    await menu.reopen();
  }
}
