# 02a: E2E stack, helpers, and CI

**What to build:** The Docker stack for E2E, the Playwright project in `e2e/` with its helpers, and the GitHub Actions workflow, proved by one smoke test on the AngularJS client. See `AngularRewrite.md`, Sections 2.3 and 2.4. Follow the rules in ticket 02.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] A second Docker Compose project for E2E: its own database volume and port, Google's public reCAPTCHA test keys, Mailpit. No change to the API code. The developer database is not touched
- [ ] `e2e/` Playwright project in TypeScript with projects for Chromium, Firefox, WebKit, and Edge (`msedge`), and one setting that selects the client under test
- [ ] The database is reset before each run, and the Pro Player list loads once
- [ ] API setup helpers: register and log in a Commissioner and an Admin, create a Draft in each status, add Managers, Round Times, Picks, and Trades
- [ ] Mailpit helper that reads the Email Verification Key and the Reset Key
- [ ] One timestamp helper for server time. It is the only code that writes to the E2E database directly
- [ ] A way to mark a test as a known failure on AngularJS only. The test still runs and must pass on the new client
- [ ] A request intercept helper for the "API error" rows
- [ ] One smoke test, LOGIN-02: it uses the API setup helpers and passes in all four browsers. Ticket 02b owns the row and fills in its columns
- [ ] GitHub Actions workflow: `composer check`, then the E2E suite. Chromium on each push, all four browsers before merge if the full run is slow
- [ ] `e2e/README.md`: how to start the stack, run the suite, select the client, and use each helper
