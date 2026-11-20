import { Browser, BrowserContext, expect, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from './api';
import { recordApiRequests } from './intercept';

/** The client asks the API for an In Progress Draft at this interval. */
export const DRAFT_POLL_MS = 2_750;

/** Runs the client clock over several poll intervals and checks that the client sends no request for the Draft. */
export async function expectNoDraftPolls(page: Page, draft: Pick<Draft, 'id'>, intervals = 3): Promise<void> {
  const polls = recordApiRequests(page, { path: `draft/${draft.id}` });

  await page.clock.runFor(DRAFT_POLL_MS * intervals);

  expect(polls).toEqual([]);
}

/** Opens the Draft screen and waits until the Draft has loaded. Returns the route. */
export async function openDraftScreen(page: Page, draft: Draft): Promise<string> {
  const route = `/draft/${draft.id}`;

  await page.goto(route);
  await expect(page.getByText(draft.name).first()).toBeVisible();

  return route;
}

/** Installs the Playwright clock, then opens the Draft screen. Move the clock by `DRAFT_POLL_MS` to run the next poll. */
export async function openDraftScreenWithClock(page: Page, draft: Draft): Promise<string> {
  await page.clock.install();

  return openDraftScreen(page, draft);
}

/** A second viewer: a new browser context with its own cookies and clock. Close the context at the end of the test. */
export async function newViewerPage(browser: Browser): Promise<{ context: BrowserContext; page: Page }> {
  const context = await browser.newContext();

  return { context, page: await context.newPage() };
}

/**
 * Logs in a new Commissioner and opens the Draft screen of their In Progress Draft. This is the starting state of
 * the rows where a logged-in viewer opens an auth route and returns to the previous route.
 */
export async function openDraftScreenLoggedIn(
  page: Page,
  context: BrowserContext,
  setup: ApiSetup,
): Promise<{ commissioner: LoggedInUser; draft: Draft; draftRoute: string }> {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
  await setup.logInBrowser(context, commissioner);

  const draftRoute = await openDraftScreen(page, draft);

  return { commissioner, draft, draftRoute };
}
