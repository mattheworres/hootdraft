import { test } from '@playwright/test';
import { STACK_URL } from './stack';

/** The two clients that the suite can run against. `E2E_CLIENT` selects one. */
const CLIENTS = {
  angularjs: { baseURL: STACK_URL },
  // Phase 4 of AngularRewrite.md serves the new client next to the AngularJS client. Until then nothing listens here.
  angular: { baseURL: 'http://localhost:8101' },
} as const;

export type ClientName = keyof typeof CLIENTS;

function selectedClient(): ClientName {
  const name = process.env.E2E_CLIENT ?? 'angularjs';

  if (!Object.hasOwn(CLIENTS, name)) {
    throw new Error(`E2E_CLIENT is "${name}". Use one of: ${Object.keys(CLIENTS).join(', ')}.`);
  }

  return name as ClientName;
}

export const clientUnderTest: ClientName = selectedClient();
export const clientBaseURL: string = CLIENTS[clientUnderTest].baseURL;

/**
 * Marks the test as a known failure on the AngularJS client. Call it first in the test body.
 * The test still runs. On AngularJS it must fail, so the mark cannot outlive the defect.
 * On the new client it must pass.
 *
 * @param defect The item number in the list at the end of docs/flow-inventory.md, for example "W-05".
 */
export function knownFailureOnAngularJs(defect: string): void {
  test.fail(clientUnderTest === 'angularjs', `Known defect ${defect} in the AngularJS client (docs/flow-inventory.md)`);
}

/** The text of the footer link to the project. W-29 of docs/flow-inventory.md: each client shows its own version. */
export const footerVersionText: string = clientUnderTest === 'angularjs' ? 'HootDraft v2.4.0' : 'HootDraft v3.0.0';
