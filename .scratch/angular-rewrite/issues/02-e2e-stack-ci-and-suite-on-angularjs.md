# 02: E2E stack, CI, and the suite on AngularJS

**What to build:** The Playwright suite in `e2e/`, its Docker stack, and the GitHub Actions workflow. Every row of `docs/flow-inventory.md` gets a test that passes on the AngularJS client. See `AngularRewrite.md`, Sections 2.3 and 2.4.

**Blocked by:** 01

**Status:** ready-for-agent

**Note:** Split this ticket before the work starts: `02a` for the stack, helpers, and CI with one smoke test, then one ticket for each route group of the inventory.

- [ ] A second Docker Compose project for E2E: its own database volume and port, Google's public reCAPTCHA test keys, Mailpit. No change to the API code. The developer database is not touched
- [ ] `e2e/` Playwright project in TypeScript with projects for Chromium, Firefox, WebKit, and Edge (`msedge`), and one setting that selects the client under test
- [ ] The database is reset before each run, and the Pro Player list loads once
- [ ] API setup helpers: register and log in a Commissioner and an Admin, create a Draft in each status, add Managers, Round Times, Picks, and Trades
- [ ] Mailpit helper that reads the Email Verification Key and the Reset Key
- [ ] One timestamp helper for server time. It is the only code that writes to the E2E database directly
- [ ] Locators use role and label first, then `data-testid` where role and label are weak. Visible text is for assertions only. No `ng-*` attributes, no CSS structure, and no existing `id` values
- [ ] Edits to `app/` templates add `data-testid` attributes only. Each attribute name describes the element with `GLOSSARY.md` terms, for example `pick-row`
- [ ] Multi-viewer tests use two browser contexts
- [ ] A test for a defect in the AngularJS client asserts the correct behavior and is marked as a known failure on AngularJS. The inventory row records it. The maintainer approves each one
- [ ] Screenshot report tool: for a list of screens, capture the old client and the new client in Chromium at one desktop viewport, and write one static HTML page with old, new, difference overlay, and the percentage of changed pixels, largest difference first
- [ ] GitHub Actions workflow: `composer check`, then the E2E suite. Chromium on each push, all four browsers before merge if the full run is slow. The report is uploaded as a zip file and linked from the run summary
- [ ] Every inventory row has a test file and "passes on AngularJS" filled in
