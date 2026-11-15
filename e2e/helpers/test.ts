import { test as base } from '@playwright/test';
import { ApiSetup } from './api';

/** The `test` that each spec file imports. It adds the API setup helpers as the `setup` fixture. */
export const test = base.extend<{ setup: ApiSetup }>({
  setup: async ({}, use) => {
    const setup = new ApiSetup();

    await use(setup);
    await setup.dispose();
  },
});

export { expect } from '@playwright/test';
