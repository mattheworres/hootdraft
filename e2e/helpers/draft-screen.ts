import { BrowserContext, expect, Locator, Page } from '@playwright/test';
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

/** Opens a route and waits until the `loaded` locator is visible. */
export async function openRoute(page: Page, route: string, loaded: Locator): Promise<void> {
  await page.goto(route);
  await expect(loaded).toBeVisible();
}

/** Installs the Playwright clock, then opens a route. Move the clock by `DRAFT_POLL_MS` to run the next poll. */
export async function openWithClock(page: Page, route: string, loaded: Locator): Promise<void> {
  await page.clock.install();
  await openRoute(page, route, loaded);
}

/** Opens the Draft screen and waits until the Draft has loaded. Returns the route. */
export async function openDraftScreen(page: Page, draft: Draft): Promise<string> {
  const route = `/draft/${draft.id}`;

  await openRoute(page, route, page.getByText(draft.name).first());

  return route;
}

/** Installs the Playwright clock, then opens the Draft screen. Move the clock by `DRAFT_POLL_MS` to run the next poll. */
export async function openDraftScreenWithClock(page: Page, draft: Draft): Promise<string> {
  await page.clock.install();

  return openDraftScreen(page, draft);
}

/** A panel of the Draft screens by its test ID. The Draft index keeps the panels of other views in the page, so only the visible one counts. */
export function visiblePanel(scope: Page | Locator, testId: string): Locator {
  return scope.getByTestId(testId).filter({ visible: true });
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
