import { Page } from '@playwright/test';
import { API_URL } from './stack';

export interface ApiFailure {
  /** The HTTP method to match. Default: each method. */
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  /** The path after `/api/`, for example `drafts` or `draft/12/picks`. It must match the whole path. */
  path: string | RegExp;
  /** Default: 500. */
  status?: number;
  /** The JSON body of the answer. Default: what the API sends for an unexpected error. */
  body?: unknown;
}

/**
 * Makes one API route fail in this page, for the "API error" rows of the inventory. The request never reaches
 * the API, so no state changes. Call it before the step that sends the request.
 */
export async function failApiRequest(page: Page, failure: ApiFailure): Promise<void> {
  const apiPath = new URL(API_URL).pathname;

  await page.route(
    (url) => {
      if (!url.pathname.startsWith(apiPath)) {
        return false;
      }

      const path = url.pathname.slice(apiPath.length);

      return typeof failure.path === 'string' ? path === failure.path : failure.path.test(path);
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
