import { BrowserContext, expect, Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser } from './api';
import { recordApiRequests } from './intercept';
import { VISIBLE_TEXT } from './text';
import { expectToast } from './toast';

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

/** Builds the route of one Draft screen. */
export type DraftRoute = (draft: Pick<Draft, 'id'>) => string;

/** The warning toast of a Draft screen that needs a Draft past Setting Up. */
export const SETTING_UP_TOAST = 'Draft is still setting up';

/** The warning toast and the return to the route that the viewer opened before. */
export async function expectRedirectToPreviousRoute(page: Page, message: string, previousRoute: string): Promise<void> {
  await expectToast(page, { message });
  await expect(page).toHaveURL(previousRoute);
}

/**
 * The viewer opens a Draft screen of an In Progress Draft, then opens `route` of a Draft in `status`. Checks the
 * warning toast `message` and the return to the first screen. Covers the "redirects for a Draft status" rows.
 */
export async function expectDraftStatusRedirect(
  page: Page,
  setup: ApiSetup,
  redirect: { route: DraftRoute; status: 'Setting Up' | 'In Progress'; message: string },
): Promise<void> {
  const commissioner = await setup.registerCommissioner();
  const target = await setup.createDraft(commissioner, { status: redirect.status });
  const other = await setup.createDraft(commissioner, { status: 'In Progress' });

  const previousRoute = await openDraftScreen(page, other);
  await page.goto(redirect.route(target));

  await expectRedirectToPreviousRoute(page, redirect.message, previousRoute);
}

/** A panel of the Draft screens by its test ID. The Draft index keeps the panels of other views in the page, so only the visible one counts. */
export function visiblePanel(scope: Page | Locator, testId: string): Locator {
  return scope.getByTestId(testId).filter({ visible: true });
}

/**
 * The Draft index of INDEX-09: the Commish Actions and League Details that the Commissioner of an In Progress Draft
 * gets.
 */
export async function expectInProgressCommishView(page: Page): Promise<void> {
  const actions = visiblePanel(page, 'commish-actions-panel');

  await expect(actions.getByRole('link').or(actions.getByRole('button'))).toHaveText(
    ['Enter Pick', 'Change Pick', 'Enter Trade', 'Reset', 'Timers', 'Delete'],
    VISIBLE_TEXT,
  );
  await expect(visiblePanel(page, 'draft-details-panel')).toContainText(
    "Can't edit active draft – Reset draft if you need to change something",
    VISIBLE_TEXT,
  );
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
