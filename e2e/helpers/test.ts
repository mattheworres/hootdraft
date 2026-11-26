import { BrowserContext, Page, test as base } from '@playwright/test';
import { ApiSetup } from './api';

/** The `test` that each spec file imports. It adds the `setup` and `newViewerPage` fixtures. */
export const test = base.extend<{ setup: ApiSetup; newViewerPage: () => Promise<Page> }>({
  setup: async ({}, use) => {
    const setup = new ApiSetup();

    await use(setup);
    await setup.dispose();
  },

  /** Opens a second viewer: a new browser context with its own cookies and clock. The test ends the context. */
  newViewerPage: async ({ browser }, use) => {
    const contexts: BrowserContext[] = [];

    await use(async () => {
      const context = await browser.newContext();
      contexts.push(context);

      return context.newPage();
    });

    await Promise.all(contexts.map((context) => context.close()));
  },
});

export { expect } from '@playwright/test';
