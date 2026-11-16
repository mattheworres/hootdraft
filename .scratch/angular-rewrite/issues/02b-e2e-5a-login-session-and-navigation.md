# 02b: E2E tests for 5A Login, session, and navigation

**What to build:** One E2E test for each row of route group 5A in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 29 rows: LOGIN-01 to LOGIN-14, NAV-01 to NAV-10, INV-01 to INV-05. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-05 (NAV-10), W-06 (LOGIN-03, LOGIN-04, LOGIN-13)
- [x] One test for each of the 29 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: NAV-06
- [x] Email comes from the Mailpit helper: LOGIN-08, INV-02
- [x] LOGIN-08 needs an expired Email Verification Key. Set its time with the timestamp helper
- [x] W-29 is not a defect. NAV-08 expects the version of the client under test: "HootDraft v2.4.0" on AngularJS and "HootDraft v3.0.0" on the new client
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group

## Comments

### 2026-11-15: implemented, awaiting review

Each item is done. The 29 tests are in `e2e/tests/5a-login.spec.ts`. The full suite passes in Chromium, Firefox, WebKit, and Edge on the AngularJS client, twice in a row. The six known failures (LOGIN-01, LOGIN-03, LOGIN-04, LOGIN-13, NAV-09, NAV-10) fail on the expected assertion. `composer check` and the CodeScene safeguard pass. The GitHub Actions workflow has not run.

Decisions for the review:

- **LOGIN-04.** As written (three or more screens), the test passes on AngularJS, so W-06 could not mark it as a known failure. The starting state is now two screens: the Draft Board, then the Draft index. This is close to LOGIN-03. The case of three or more screens has no test. Revert the row and the mark if you prefer.
- **W-35 and W-36 (new, approved 2026-11-15).** LOGIN-01 and NAV-09 now assert the correct result and are known failures on AngularJS, with "Known defect" W-35 and W-36. LOGIN-01 clicks **Lost password?** and **Create a new account!** with no focus move. NAV-09 opens the menu at phone width for **New League**, the name, the logo, and **Log Out**. Both fail on the defect, not on a locator.
- **NAV-06.** The Admin disables the user through the Admin users screen in a second browser context, with role and label locators only. The Admin templates are outside this group, so they have no new `data-testid` attributes.
- **Helpers (02a code).** `registerEmailUnverifiedUser` and `setUserEnabled` are new, with checks in `helpers.spec.ts` and entries in the README. `registerAdmin` now returns a new login session: the role change ended the first one, so the old session got a 401 on `admin/users`. `footerVersionText` in `helpers/client.ts` gives the version for NAV-08.
- **Templates.** Three `data-testid` attributes: `login-show-password`, `invite-name`, and `invite-message`. The accessible name of the **Show** switch is empty, and the two invite fields get their names from placeholders.
- **Environment.** The E2E nginx container had lost its bind mount of `docker/nginx/default.conf` (the file was replaced on disk), so `/js/dev.js` returned 404. `docker compose -f stack/docker-compose.yml up -d --force-recreate nginx` fixed it. Expect this after a branch switch.
- **Rows.** Expected results changed for LOGIN-03, LOGIN-04, LOGIN-13, NAV-08, NAV-09, and NAV-10. NAV-09 now starts with a logged-in Commissioner.
