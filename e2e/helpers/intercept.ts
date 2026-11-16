import { Page, Request } from '@playwright/test';
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
 * Makes one API route fail in this page, for the "API error" rows of the inventory. The request never reaches
 * the API, so no state changes. Call it before the step that sends the request.
 */
export async function failApiRequest(page: Page, failure: ApiFailure): Promise<void> {
  await page.route(
    (url) => {
      const path = apiPathOf(url);

      return path !== null && pathMatches(path, failure.path);
    },
    async (route) => {
      if (failure.method && route.request().method() !== failure.method) {
        return route.fallback();
      }

      await route.fulfill({
        status: failure.status ?? 500,
        contentType: 'application/json',
        body: JSON.stringify(failure.body ?? { error: 'An unexpected error occurred.' }),
      });
    },
  );
}
