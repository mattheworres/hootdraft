import { expect, Locator, Page, Request, Response } from '@playwright/test';
import { API_URL } from './stack';

export interface ApiRequestMatch {
  /** The HTTP method to match. Default: each method. */
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  /** The path after `/api/`, for example `drafts` or `draft/12/picks`. It must match the whole path. Default: each path. */
  path?: string | RegExp;
}

export interface ApiFailure extends ApiRequestMatch {
  path: string | RegExp;
  /** Default: 500. */
  status?: number;
  /** The JSON body of the answer. Default: what the API sends for an unexpected error. */
  body?: unknown;
}

/** The path after `/api/`, or null for a URL outside the API. */
function apiPathOf(url: URL): string | null {
  const apiPath = new URL(API_URL).pathname;

  return url.pathname.startsWith(apiPath) ? url.pathname.slice(apiPath.length) : null;
}

function pathMatches(path: string, expected: string | RegExp | undefined): boolean {
  if (expected === undefined) {
    return true;
  }

  return typeof expected === 'string' ? path === expected : expected.test(path);
}

function methodMatches(method: string, expected: ApiRequestMatch['method']): boolean {
  return expected === undefined || method === expected;
}

function requestMatches(request: Request, match: ApiRequestMatch): boolean {
  const path = apiPathOf(new URL(request.url()));

  return path !== null && methodMatches(request.method(), match.method) && pathMatches(path, match.path);
}

/**
 * Records the URL of each matching API request that this page sends, for the rows that say "no request is sent".
 * Call it before the step, and read the list after the step.
 */
export function recordApiRequests(page: Page, match: ApiRequestMatch = {}): string[] {
  const requests: string[] = [];

  page.on('request', (request) => {
    if (requestMatches(request, match)) {
      requests.push(request.url());
    }
  });

  return requests;
}

/**
 * Waits for the answer to the next matching API request. The `request` event fires when the request is sent, so
 * use this when the step needs the answer to have arrived. Call it before the step, and await it after the step.
 */
export function waitForApiResponse(page: Page, match: ApiRequestMatch = {}): Promise<Response> {
  return page.waitForResponse((response) => requestMatches(response.request(), match));
}

/**
 * Makes one API route fail in this page, for the "API error" rows of the inventory. The request never reaches
 * the API, so no state changes. Call it before the step that sends the request.
 */
export async function failApiRequest(page: Page, failure: ApiFailure): Promise<void> {
  await fulfillApiRequest(
    page,
    failure,
    failure.body ?? { error: 'An unexpected error occurred.' },
    failure.status ?? 500,
  );
}

/**
 * Answers each matching API request with this JSON body and status. The request never reaches the API. Use it
 * for a result that the API cannot give on demand, such as the exact durations of Draft Stats.
 */
export async function fulfillApiRequest(
  page: Page,
  match: ApiRequestMatch,
  body: unknown,
  status = 200,
): Promise<void> {
  await page.route(
    (url) => apiPathOf(url) !== null,
    async (route) => {
      if (!requestMatches(route.request(), match)) {
        return route.fallback();
      }

      await route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(body) });
    },
  );
}

/**
 * Holds the answer to each matching API request until the returned function is called, so a loading indicator stays
 * long enough to see. The request still reaches the API, so its state change happens at once. Call it before the
 * step that sends the request.
 */
export async function holdApiResponses(page: Page, match: ApiRequestMatch): Promise<() => void> {
  let release = () => {};
  const released = new Promise<void>((resolve) => (release = resolve));

  await page.route(
    (url) => apiPathOf(url) !== null,
    async (route) => {
      if (!requestMatches(route.request(), match)) {
        return route.fallback();
      }

      const response = await route.fetch();
      await released;
      await route.fulfill({ response });
    },
  );

  return release;
}

export interface LoadingCheck {
  /** The text of the indicator. Default: "Loading...". */
  text?: string;
  /** Checks that the step makes while the indicator shows. */
  whileLoading?: () => Promise<void>;
}

/**
 * For the rows of a slow request: checks that the indicator shows in `scope` while the held answer is pending, then
 * releases it and checks that the indicator goes.
 */
export async function expectLoadingUntilReleased(
  scope: Page | Locator,
  release: () => void,
  { text = 'Loading...', whileLoading = async () => {} }: LoadingCheck = {},
): Promise<void> {
  const indicator = scope.getByText(text);

  try {
    await expect(indicator).toBeVisible();
    await whileLoading();
  } finally {
    release();
  }
  await expect(indicator).toBeHidden();
}
