import { BrowserContext, expect, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from './api';

/** Opens the Draft screen and waits until the Draft has loaded. Returns the route. */
export async function openDraftScreen(page: Page, draft: Draft): Promise<string> {
  const route = `/draft/${draft.id}`;

  await page.goto(route);
  await expect(page.getByText(draft.name).first()).toBeVisible();

  return route;
}

/**
 * Logs in a new Commissioner and opens the Draft screen of their In Progress Draft. This is the starting state of
 * the rows where a logged-in viewer opens an auth route and returns to the previous route.
 */
export async function openDraftScreenLoggedIn(
  page: Page,
  context: BrowserContext,
  setup: ApiSetup,
): Promise<{ commissioner: LoggedInUser; draftRoute: string }> {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await setup.logInBrowser(context, commissioner);

  const draftRoute = await openDraftScreen(page, draft);

  return { commissioner, draftRoute };
}
