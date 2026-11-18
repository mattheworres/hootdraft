import { defineConfig, devices } from '@playwright/test';
import { clientBaseURL } from './helpers/client';

const HELPER_TESTS = /helpers\.spec\.ts/;

export default defineConfig({
  testDir: './tests',
  globalSetup: './global-setup.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  reporter: [
    process.env.CI ? ['github'] : ['list'],
    ['html', { open: 'never' }],
    ['json', { outputFile: 'test-results/results.json' }],
  ],
  use: {
    baseURL: clientBaseURL,
    trace: 'retain-on-failure',
    testIdAttribute: 'data-testid',
  },
  projects: [
    // Checks of the helpers in helpers/. They do not test a client, so one browser is enough.
    { name: 'helpers', testMatch: HELPER_TESTS, use: { ...devices['Desktop Chrome'] } },
    { name: 'chromium', testIgnore: HELPER_TESTS, use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', testIgnore: HELPER_TESTS, use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', testIgnore: HELPER_TESTS, use: { ...devices['Desktop Safari'] } },
    { name: 'edge', testIgnore: HELPER_TESTS, use: { ...devices['Desktop Edge'], channel: 'msedge' } },
  ],
});
