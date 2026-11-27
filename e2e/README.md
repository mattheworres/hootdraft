# E2E suite

One Playwright suite for two clients: the AngularJS client in `app/` and the new Angular client. The suite belongs to neither client. See `AngularRewrite.md`, Section 2.3, and the rules in `.scratch/angular-rewrite/issues/02-e2e-stack-ci-and-suite-on-angularjs.md`.

## Start the stack

The suite runs against its own Docker Compose project, `hootdraft-e2e`. It has its own database volume, so the developer database is never touched. The two stacks can run at the same time.

| What | Where |
| --- | --- |
| Client and API | `http://localhost:8100` |
| Email inbox (Mailpit) | `http://localhost:8125` |
| MariaDB | `127.0.0.1:3407`, database `hootdraft_e2e`. The passwords are in `stack/e2e.env` |

1. Build the AngularJS client at the repo root, as for the developer box: `yarn install`, then `npx gulp build`. The stack serves the same build. Build before you start the stack for the first time.
2. Install the PHP packages, if the developer box did not: `docker compose exec php composer install`.
3. In `e2e/`, install the packages and the browsers:

   ```sh
   npm install
   npx playwright install chromium firefox webkit msedge
   ```

4. Start the stack:

   ```sh
   npm run stack:up        # start
   npm run stack:down      # stop
   npm run stack:destroy   # stop and delete the E2E database volume
   ```

The stack differs from the developer box in these ways:

- **reCAPTCHA.** The API and the client use Google's public test keys, so each check passes.
- **Client settings.** nginx answers `/js/dev.js` with `stack/nginx/client-config.js`, not with the generated file. One client build works for both stacks.
- **API mode.** The API runs as in production (`APP_ENV=prod`, no debug), with its own `var/` directory.
- **Draft cache.** `CACHE_SECONDS` is `1`. See the timestamp helper.

After you change a template in `app/`, build the client again at the repo root (`npx gulp buildJs` is enough). The stack serves the new build at once.

## Run the suite

Run the whole suite in Chromium before you mark the work done: `--project=chromium --project=helpers`, with no file or `-g` filter. CI runs Firefox, WebKit, and Edge on a pull request. Run another browser locally only to fix a failure that CI shows.

```sh
npm test                               # all four browsers and the checks of the helpers (CI runs this)
npm run test-ui                        # open Playwright's UI mode
npx playwright test --project=chromium # one browser: chromium, firefox, webkit, or edge
npx playwright test --project=chromium --project=helpers # whole suite
npx playwright test 5a-login           # one file
npx playwright test -g LOGIN-02        # one inventory row
npx playwright show-report             # the report of the last run
# Each run also writes test-results/results.json: results per test and browser.
npm run typecheck
npm run format
```

Before each run, the global setup (`global-setup.ts`) does the following:

1. Drops the E2E database and runs the migrations.
2. Deletes each email in Mailpit.
3. Creates the first Admin (`FIRST_ADMIN` in `helpers/stack.ts`).
4. Loads the Pro Player list of each League from `resources/`.

Tests run at the same time on one database. Each test creates its own users and Drafts with names that no other test uses. Do not write a test that depends on the database being empty.

## Select the client

The `E2E_CLIENT` environment variable selects the client under test:

| Value | Client | Base URL |
| --- | --- | --- |
| `angularjs` (default) | `app/` | `http://localhost:8100` |
| `angular` | `client/` | `http://localhost:8101`. Phase 4 serves the new client here |

```sh
E2E_CLIENT=angular npm test
```

## Helpers

Import `test` and `expect` from `helpers/test.ts`, not from `@playwright/test`. This `test` adds the `setup` fixture and the `newViewerPage` fixture. `await newViewerPage()` opens a second viewer: a page in a new browser context with its own cookies and clock. The test closes the context when it ends.

### API setup: `setup` fixture (`helpers/api.ts`)

Each test creates its state through the API. Only the flow under test goes through the UI.

```ts
import { expect, test } from '../helpers/test';

test('DRAFT-01 ...', async ({ page, context, setup }) => {
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress', managers: ['Ann', 'Bob'] });
  const pick = await setup.addPick(commissioner, draft, { firstName: 'Davante', lastName: 'Adams' });

  await setup.logInBrowser(context, commissioner); // skip the login screen
  await page.goto(`/draft/${draft.id}`);
});
```

| Method | Result |
| --- | --- |
| `registerCommissioner(overrides?)` | A new Enabled, Email Verified user with a login session. Pass `name`, `email`, or `password` to set them |
| `registerAdmin(overrides?)` | The same, with the Admin role |
| `registerEmailUnverifiedUser(overrides?)` | A new Email Unverified user. Returns the credentials only. The registration email is in Mailpit |
| `logIn(credentials)` | A new login session for an existing user |
| `setUserEnabled(admin, user, enabled)` | Enables or disables a user. A Disabled user cannot log in, and their login session ends |
| `createDraft(commissioner, options?)` | A Draft. `status` is `'Setting Up'` (default), `'In Progress'`, or `'Complete'`. Other options: `name`, `league`, `style`, `rounds`, `password` (the Draft Password), `managers`, `depthChartPositions` |
| `setDraftStatus(commissioner, draft, status)` | Moves a Draft to Setting Up or In Progress |
| `draftStatus(commissioner, draft)` | The status of a Draft now: `'Setting Up'`, `'In Progress'`, or `'Complete'` |
| `addManagers(commissioner, draft, names)` | Adds Managers. Returns each Manager in Draft Order |
| `setRoundTimes(commissioner, draft, roundTimes)` | Replaces the Round Times. `[{ seconds: 60 }]` is one time for each round. `[{ round: 1, seconds: 60 }, ...]` is a time for each round |
| `addPick(commissioner, draft, entry?)` | Enters the current Pick. Returns its ID, round, number, and Manager |
| `addPicks(commissioner, draft, count)` | Enters the next `count` Picks. Returns the Selected Picks in Pick order |
| `currentPick(commissioner, draft)` | The Pick that is on the clock: its ID, round, number, and Manager |
| `editPick(commissioner, draft, pick)` | Saves the name, team, and position of a Selected Pick. Pass a copy of the Pick that `addPick` returned, with the new values |
| `addTrade(commissioner, draft, trade)` | Enters a Trade: `manager1Id`, `manager2Id`, `pickIds` |
| `uploadProPlayers(admin, league, csvPath)` | Replaces the Pro Player list of one League |
| `requestPasswordReset(user)` | Starts the lost password process. The lost password email with the Reset Key is in Mailpit |
| `resetPassword(user, resetKey, password)` | Sets the password with a Reset Key, which uses the key up |
| `inviteUser(inviter, overrides?)` | Invites a new user. The invite email with the Reset Key is in Mailpit. Returns `name` and `email` |
| `logInBrowser(context, user)` | Gives a browser context the login session of the user |

A user that a helper returns has `name`, `email`, `password`, and `api`. Use `user.api` to send any other API request as that user, for example `await commissioner.api.get('commish/profile')`.

A Complete Draft gets a Pick for each Manager in each round, because a Draft becomes Complete only with its last Pick.

The helpers send a request again if the API answers with a database deadlock. See "Known limits".

### Mailpit: `helpers/mailpit.ts`

```ts
const { key, link } = await readEmailVerificationKey(email); // the newest email with a /verify link
const { key, link } = await readResetKey(email);             // the newest lost password email or invite email
```

Each function waits up to ten seconds for the email. `link` is the emailed link, so a test can open it with `page.goto(link)`.

`linkPath(link)` is the path of the link. Open that path, not the link: the host in the email can differ from the client under test. `withWrongKey(path)` replaces the key at the end of a path with a key that matches no user.

### Form validation: `helpers/form.ts`

`emailField(page)` finds the Email field. `expectEmailValidationMessages(page)` checks the messages of the Email field. `expectPasswordValidationMessages(page, field)` and `expectConfirmValidationMessages(page, field)` check the messages of a Password and a Confirm field. Fill the Password field before the Confirm check.

### Navigation: `helpers/navigation.ts`

`userLink(page, name)` finds the link in the navigation that shows the logged-in user's name. `logoLink(page)` finds the logo link, which opens /home.

`expectMenuLinksOpenRoutes(page, { linkByName, reopen }, routes)` selects each link of an open menu and checks its route. `routes` is a list of `{ link, url }`. `reopen` opens the menu again after each link.

### reCAPTCHA: `helpers/recaptcha.ts`

`recaptchaCheckbox(page)` finds the box by its iframe title. `completeRecaptcha(page)` clicks it and waits until it is checked. The E2E stack uses Google's public test keys, so no challenge shows.

### Server time: `helpers/timestamps.ts`

The Playwright clock (`page.clock`) controls client timers. For server time, move a stored timestamp into the past:

```ts
await setDraftStartTime(draft.id, { minutes: 4 });      // the Pick Timer of the first Pick counts from here
await setPickTime(pick.id, { seconds: 50 });            // the Pick Timer of the next Pick counts from here
await setDraftCreateTime(draft.id, { hours: 48 });     // the Draft list orders by this time
await setDraftEndTime(draft.id, { minutes: 11 });       // past the ten-minute rule for a Complete Draft
await setEmailVerificationKeyTime(email, { hours: 49 }); // the key expires after 48 hours
await setResetKeyTime(email, { hours: 2 });             // the key expires after one hour
```

- This file is the only code that writes test state to the E2E database. If a test needs a different timestamp, add a function here.
- The age is measured on the database clock, not on the clock of your machine.
- The API caches each Draft row. The two Draft functions wait two seconds, so that the cached row expires.

### Client under test: `helpers/client.ts`

`footerVersionText` is the text of the footer link to the project, which differs by client: "HootDraft v2.4.0" on AngularJS and "HootDraft v3.0.0" on the new client.

#### Known failure on AngularJS

For a row with a "Known defect" value, assert the correct result and mark the test:

```ts
test('NAV-10 ...', async ({ page }) => {
  knownFailureOnAngularJs('W-05');
  // assert the correct result
});
```

The test still runs. On the AngularJS client it must fail: if it passes, the run fails, so remove the mark. On the new client the mark does nothing, and the test must pass.

### API errors: `helpers/intercept.ts`

For the "API error" rows, make one API route fail before the step that sends the request:

```ts
await failApiRequest(page, { method: 'GET', path: 'drafts' });                 // 500
await failApiRequest(page, { path: /^draft\/\d+\/picks$/, status: 404, body: { success: false } });
```

`path` is the path after `/api/`. The request never reaches the API.

For the rows that say "no request is sent", record the matching API requests before the step and check the list after it:

```ts
const loginRequests = recordApiRequests(page, { path: 'login' });
// the step
expect(loginRequests).toEqual([]);
```

`recordApiRequests` sees a request when it is sent. When the step needs the answer to have arrived, wait for it:

```ts
const failedPoll = waitForApiResponse(page, { path: `draft/${draft.id}` });
// the step
await failedPoll;
```

To answer a request with a body of your own, when the API cannot give it on demand (for example the exact durations of Draft Stats), use `fulfillApiRequest`. The status is 200 unless you pass another. The request never reaches the API:

```ts
await fulfillApiRequest(page, { method: 'GET', path: `draft/${draft.id}/stats` }, body);
```

To keep a loading indicator on screen, hold the answer to a request until the step is done. The request still reaches the API:

```ts
const release = await holdApiResponses(page, { path: 'register' });
// the step, then the checks of the loading indicator
release();
```

### Draft screen: `helpers/draft-screen.ts`

`openDraftScreen(page, draft)` opens the Draft screen, waits until the Draft has loaded, and returns the route. `openDraftScreenWithClock(page, draft)` installs the Playwright clock first. `openRoute(page, route, loaded)` opens any route and waits until the `loaded` locator is visible, and `openWithClock` installs the clock first. `visiblePanel(scope, testId)` finds the visible panel with that test ID: the Draft index keeps the panels of other views in the page. `openDraftScreenLoggedIn(page, context, setup)` does the same as `openDraftScreen` for a new, logged-in Commissioner with an In Progress Draft, and returns the Commissioner, the Draft, and the route. It is the starting state of the rows where a logged-in viewer opens an auth route. `DRAFT_POLL_MS` is the interval at which the client polls an In Progress Draft. Move the Playwright clock by it to run the next poll.  `expectDraftStatusRedirect(page, setup, { route, status, message })` covers the "redirects for a Draft status" rows: the viewer opens a Draft screen, then the route of a Draft in `status`, and the test checks the warning toast and the return to the first screen. `expectRedirectToPreviousRoute(page, message, previousRoute)` is its second half, for a test that sets up the first screen itself. `SETTING_UP_TOAST` is the warning toast for a Setting Up Draft. A `DraftRoute` builds the route of one Draft screen from the Draft. `expectNoDraftPolls(page, draft, intervals?)` runs the clock over three intervals, or `intervals`, and checks that no request for the Draft is sent. Install the clock first.

### Visible text: `helpers/text.ts`

`VISIBLE_TEXT` is the option for `toHaveText` and `toContainText` that skips hidden text, such as error panels and phone-width variants of a view.

### Toasts: `helpers/toast.ts`

```ts
await expectToast(page, { title: 'Logged In', message: 'Welcome back, Ann!' });
```

The AngularJS toast has no role and no `data-testid`, so this helper asserts the visible text. `expectAlreadyLoggedInToast(page, user)` asserts the toast that a logged-in viewer gets on an auth route.

`expectToast` passes when the first toast shows, so it cannot see a duplicate that arrives a moment later. `expectSingleToast(page, message)` waits for the network to go quiet, then asserts exactly one toast with that message.

## CI

`.github/workflows/ci.yml` runs `composer check` in the developer box, then this suite. A push runs Chromium. A pull request runs Chromium, Firefox, WebKit, and Edge. A failed run keeps its report as an artifact.

## Known limits

- **The first Admin.** The API has no route that makes the first Admin. The global setup gives the role with one SQL statement, as part of the database reset.
- **Deadlocks in the API.** When two requests move a Draft to In Progress at the same time, the API can refuse one with a database deadlock. Ticket 02v fixes the API. Until then, the setup helpers send the request again, and a test that moves a Draft to In Progress through the UI can still meet this error.
- **The Docker clock.** After a laptop sleeps, the clock of the Docker VM can be behind. The timestamp helper is safe. Restart Docker if a run is slow or times out.
