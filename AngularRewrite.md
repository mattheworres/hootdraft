# HootDraft: Angular rewrite

The client is AngularJS 1.8, which has been end of life since 2022. Its build (gulp, Babel 6, webpack 4) and its widget libraries are also dead. This plan replaces the client with a new Angular app that looks the same, and ends with the relaunch of the public HootDraft instance.

This plan starts after the PHP rewrite (`PHP8Rewrite.md`) merges into the Develop branch. The work is done on a new branch from `develop`.

Related documents:

- `docs/adr/0004-angular-client-architecture.md` and `docs/adr/0005-no-server-side-rendering.md`
- Issue files: `.scratch/angular-rewrite/issues/`, one for each phase
- `GLOSSARY.md` for all domain terms

## 1. Current state (measured 2026-11-14)

- 47 components, 16 directives, 14 services, 26 routes, 60 templates, about 7,400 lines of JavaScript. No client tests.
- HTML5 URLs (no `#`).
- Styles: Bootstrap 3.4.1, Font Awesome 4.7, 10 Less files.
- About 15 AngularJS-only widget libraries. None has an Angular version.
- Live Draft updates use polling (`$interval` in `app/features/shared/services/draft.service.js`).
- The flip clock (FlipClock 0.7.7 plus `angular-flipclock`) is used in one place: the pick countdown on the Draft Board.
- The repository has no CI workflow.

## 2. Decisions

### 2.1 Target

1. **Clean rewrite.** A new Angular project in `client/`. No `ngUpgrade` hybrid. The AngularJS app in `app/` is the reference and does not change until cutover. The one exception is `data-testid` attributes added to its templates (Section 2.3, item 3).
2. **Versions.** The latest stable Angular and the latest stable TypeScript when phase 4 starts. On 2026-11-14 this is Angular 22.x; Angular 23 is expected soon. Confirm on angular.dev before `ng new`. TypeScript runs in strict mode.
3. **Angular rules.** Standalone components, zoneless change detection, lazy-loaded routes.
4. **Signals only in components.** A component reads signals and calls store methods. No component subscribes to an Observable.
5. **State.** NgRx SignalStore. RxJS stays inside the stores (HTTP calls, polling).
6. **Draft updates seam.** The draft store gets updates from one "draft updates" source. Polling is the only implementation in this plan. A later refactor can add a Mercure (server-sent events) implementation with no change to components. Nothing for Mercure is built now.
7. **No SSR and no prerender.** See `docs/adr/0005-no-server-side-rendering.md`.
8. **Styles do not change.** Bootstrap 3.4.1 CSS, Font Awesome 4.7 and the Less files stay. A temporary legacy CSS file is permitted where a rule has no home yet. A Bootstrap upgrade is a later refactor.
9. **Dependency policy.** Permitted: Angular, Angular CDK, NgRx SignalStore, RxJS. Each other package needs the maintainer's approval. Widgets (modals, tooltips, dropdowns, toasts, tables, and drag and drop) are hand-written and output the Bootstrap 3 markup. jQuery, the Bootstrap JavaScript, moment, and lodash are removed.
10. **Language.** The client uses the `GLOSSARY.md` terms in stores, components, and types. One thin API layer mirrors the API field names exactly (for example `draft_sport`) and maps them. A new term goes into `GLOSSARY.md` when it appears.
11. **API freeze.** Same URLs, same response shapes, polling stays. Only fixes for defects that the E2E suite finds are permitted. Expect such fixes: the plan could not predict each defect that the suite finds. Each fix is its own ticket, and the plan records it under "Known compromises". An API field rename is a later refactor. reCAPTCHA stays at its current version.
12. **URLs.** All 26 client URLs stay identical.

### 2.2 Build stack

All of it comes from the Angular CLI: the TypeScript and Angular compilers, esbuild for bundles, Vite for the development server, Less support, hashed file names, angular-eslint, and Vitest for unit tests. The output is static files for nginx. Node.js is necessary on the build machine and in CI only, not on the server.

### 2.3 Tests

1. **Flow inventory.** `docs/flow-inventory.md` defines "100% of flows". One row for each route, for each viewer (anonymous, Commissioner, Admin), and for each Draft status (Setting Up, In Progress, Complete) where the screen changes. Validation messages and error states are rows. Multi-viewer flows are rows.
2. **One E2E suite, two clients.** A Playwright project in `e2e/` (TypeScript) that belongs to neither client. A setting selects the client under test. The suite is written first, against AngularJS.
3. **Locators.** Tests find elements by role and label first. Where role and label are weak (Draft Board cells, Pick rows, Manager rows, table columns), tests use a `data-testid` attribute, and the two clients carry the same attribute values. Visible text is for assertions, not for finding controls. No `ng-*` attributes, no CSS structure, and no existing `id` values.
4. **Browsers.** Chromium, Firefox, WebKit, and Edge (`msedge` channel). All four are the gate before merge. WebKit is not real Safari, so real Safari is checked by hand before relaunch.
5. **E2E stack.** A second Docker Compose project with its own database volume and port, Google's public reCAPTCHA test keys, and Mailpit. The API code does not know about tests. The developer database is never touched.
6. **Test data.** Each test creates its state through the API. The database is reset before each run. The Pro Player list loads once for each run. Email Verification Keys and Reset Keys are read from the Mailpit API. Only the flow under test goes through the UI.
7. **Time.** The Playwright clock controls client timers. For server time (Round Times, the ten-minute rule for a Complete Draft), one helper writes timestamps directly to the E2E database. No other test writes to the database directly. The database reset before each run is not a test: it creates the database, runs the migrations, and gives the first Admin its role.
8. **Multi-viewer flows.** Two browser sessions at the same time: a Commissioner enters a Pick and a second viewer sees it. This includes the Draft Password prompt.
9. **Defects in the AngularJS client.** The test asserts the correct behavior, is marked as a known failure on AngularJS, and is recorded in the inventory. Each such row needs the maintainer's approval. The AngularJS code is not fixed.
10. **Unit tests.** Vitest, for stores and pure logic only (Draft Order, timer calculation, polling state). Written test-first. Components have no unit tests.
11. **Appearance.** Each pull request gets a screenshot report for the screens that it migrates: old client, new client, and a pixel difference overlay. Chromium only, one desktop viewport. The report is a static HTML page in a zip file linked from the CI summary. It is a review aid, not a gate.

### 2.4 Process

1. **Layout.** `app/` (AngularJS, untouched except for `data-testid` attributes), `client/` (new), `e2e/`. Delete `app-new/`.
2. **CI.** GitHub Actions starts the Docker box and runs `composer check`, the client lint and unit tests, and the E2E suite.
3. **CI gate.** On each run, the full suite must pass on the AngularJS client, and the tests for inventory rows marked as migrated must pass on the new client.
4. **Pull requests.** One for each route group. Merge when CI passes. The new client merges into `develop` during the work but is not served to users until cutover.

## 3. Phases

Phases 5 to 8 list screens, not commits. Each route group in a phase is one pull request, and each pull request leaves the app in a working state.

Each phase has an issue file with the same number in `.scratch/angular-rewrite/issues/`. A feature phase (5 to 8) is done when its inventory rows pass on the new client in all four browsers.

1. **Flow inventory.** Read every route, component, and template in `app/`. Write `docs/flow-inventory.md`. The maintainer reviews it.
   Done when: the maintainer approves the inventory.
2. **E2E stack, CI, and the suite on AngularJS.** Build the E2E Compose project, the API setup helpers, the Mailpit helper, the timestamp helper, the GitHub Actions workflow, and the screenshot report tool. Write the tests for every inventory row.
   Done when: every row has a test, the suite passes on AngularJS in four browsers in CI, and each known failure is approved.
3. **Flip clock prototype.** FlipClock 1.0.1 (August 2025) is a rewrite in TypeScript without jQuery, but it includes `solid-js` and `goober` (run-time CSS injection). Build a throwaway zoneless Angular page with the pick countdown. Write a report: appearance against the current clock, behavior without zone.js, behavior under a strict Content Security Policy, bundle size, and for `solid-js` and `goober` the size, maintainers, and release history.
   **Stop here.** The maintainer chooses: use FlipClock 1.0.1, or build a framework-agnostic clock as a custom element in its own repository. The second choice adds discrete steps to this plan before phase 6.
   Done when: the decision is recorded here and in the issue file.
4. **Angular skeleton.** `ng new` in `client/`, zoneless, strict. Routing with all 26 URLs (placeholders), the API layer with the term mapping, the login session store (`/me`, login, logout, the 401 handling, the `X-Requested-With` header), navigation, modals, toasts, the loading indicator, the Less build, and nginx and Compose changes so that the two clients can be served side by side.
   Done when: the new client builds, serves, shows the navigation with the correct login state, and CI runs its lint and unit tests.
5. **Authentication and home.** Login, register, the email link screen (`/verify`), lost password, Reset, invite setup, profile, and the home page with the Draft table (sort, filters, saved table state).
6. **Draft store and public Draft screens.** The draft store with the draft updates seam and polling. Draft index (three statuses), Draft Board with the flip clock wrapper, Picks by Round and by Manager, search, Draft Stats, Trades, Depth Chart, the Draft Password prompt. This is the highest-risk phase.
7. **Commissioner screens.** Create and edit a Draft, Managers (add, add many, reorder, delete), Round Times, add and edit Picks with the Pro Player search, add a Trade, Draft status changes.
8. **Admin screens.** Users, Pro Player upload, regenerate Draft Stats.
9. **Cutover.** Delete `app/`, `app-new/`, gulp, Babel, webpack, and the old packages. Point nginx, the Docker box, and the Deployer tasks (`deploy/`) at the new build output. The E2E suite targets one client. Compare the full screenshot set once.
   Done when: the repository has no AngularJS code, and the full suite passes on the new client in four browsers.
10. **Relaunch.** Content Security Policy and security headers in nginx. Dependency audit in CI (`composer audit`, `npm audit`). A deploy to a staging host (the PHP rewrite did not test the deploy on a real server). The manual check on real Safari. The check with the real reCAPTCHA keys. Then the production deploy.
    Done when: the public instance is live.

## 4. Future work (not in scope)

- Mercure in place of polling, optional and configurable.
- A newer reCAPTCHA version.
- A Bootstrap upgrade.
- API field renames to the glossary terms.

## 5. Known compromises

Record each sidestep here during the work, as in `PHP8Rewrite.md`.

- WebKit in Playwright is not real Safari. A manual check covers the gap.
- The screenshot report uses one desktop viewport. Phone widths are not compared.
- The API keeps old terms (`draft_sport`, "player" for a Pick) until a later refactor. The client API layer hides them.
- The API has no route that makes the first Admin. The E2E database reset gives the role with one SQL statement (Section 2.3, item 7).
- The E2E stack sets `CACHE_SECONDS=1`, so that a timestamp written by the timestamp helper shows after the cached Draft row expires.
- The API can refuse a Draft status change with a database deadlock when two Drafts go In Progress at the same time. Ticket 02v fixes the API. Until then, the API setup helpers send the request again.
