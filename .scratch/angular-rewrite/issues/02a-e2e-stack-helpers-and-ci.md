# 02a: E2E stack, helpers, and CI

**What to build:** The Docker stack for E2E, the Playwright project in `e2e/` with its helpers, and the GitHub Actions workflow, proved by one smoke test on the AngularJS client. See `AngularRewrite.md`, Sections 2.3 and 2.4. Follow the rules in ticket 02.

**Blocked by:** 01

**Status:** awaiting-human-review

- [x] A second Docker Compose project for E2E: its own database volume and port, Google's public reCAPTCHA test keys, Mailpit. No change to the API code. The developer database is not touched
- [x] `e2e/` Playwright project in TypeScript with projects for Chromium, Firefox, WebKit, and Edge (`msedge`), and one setting that selects the client under test
- [x] The database is reset before each run, and the Pro Player list loads once
- [x] API setup helpers: register and log in a Commissioner and an Admin, create a Draft in each status, add Managers, Round Times, Picks, and Trades
- [x] Mailpit helper that reads the Email Verification Key and the Reset Key
- [x] One timestamp helper for server time. It is the only code that writes test state to the E2E database directly. The database reset is not test state: it creates the database, runs the migrations, and gives the first Admin its role
- [x] A way to mark a test as a known failure on AngularJS only. The test still runs and must pass on the new client
- [x] A request intercept helper for the "API error" rows
- [x] One smoke test, LOGIN-02: it uses the API setup helpers and passes in all four browsers. Ticket 02b owns the row and fills in its columns
- [x] GitHub Actions workflow: `composer check`, then the E2E suite. Chromium on each push, all four browsers before merge if the full run is slow
- [x] `e2e/README.md`: how to start the stack, run the suite, select the client, and use each helper

## Additions beyond the list above

These came up during the work. The maintainer accepted them on 2026-11-15, so a review does not need to flag them as scope creep.

- [x] Checks of the helpers in `e2e/tests/helpers.spec.ts`, against the real API. They run as their own Playwright project, `helpers`, in Chromium only, because they do not test a client
- [x] `expectToast` in `e2e/helpers/toast.ts`. The AngularJS toast has no role and no `data-testid`, so later tickets share one way to assert it
- [x] `logInBrowser` in the API setup helpers, so a test can start logged in without the login screen
- [x] `setDraftStatus` and `uploadProPlayers` in the API setup helpers. `createDraft` and the database reset use them
- [x] A retry of a setup request after an API deadlock. Ticket 02v removes it
- [x] Prettier and `npm run format` for `e2e/`
- [x] An `include` line in `docker/nginx/default.conf`, so the E2E stack can answer `/js/dev.js` with its own client settings
- [x] A `concurrency` block in the workflow, so a new push cancels the older run of the same branch
- [x] The E2E stack runs the API as in production (`APP_ENV=prod`, no debug) and with `CACHE_SECONDS=1`

## Comments

### 2026-11-15: implemented, awaiting review

Each item is done. The suite passes on the AngularJS client in Chromium, Firefox, WebKit, and Edge on the maintainer's machine. The GitHub Actions workflow has not run yet: it runs for the first time when the branch is pushed.

Notes for the review:

- The API has no route that makes the first Admin. The database reset gives the role with one SQL statement, next to the statement that creates the database. The maintainer approved this on 2026-11-15: the reset is not test state.
- The API caches each Draft row, so the E2E stack sets `CACHE_SECONDS=1` and the two Draft functions of the timestamp helper wait two seconds.
- The API can refuse a Draft status change with a database deadlock when two Drafts go In Progress at the same time. The API setup helpers send the request again. Ticket 02v fixes the API.
- The login template has one new attribute: `data-testid="login-password"`. The label of that field is not bound to it, and a password field has no role.
- `E2E_CLIENT=angular` points at `http://localhost:8101`. Nothing listens there until phase 4.
