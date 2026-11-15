# 02b: E2E tests for 5A Login, session, and navigation

**What to build:** One E2E test for each row of route group 5A in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 29 rows: LOGIN-01 to LOGIN-14, NAV-01 to NAV-10, INV-01 to INV-05. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-05 (NAV-10), W-06 (LOGIN-03, LOGIN-04, LOGIN-13)
- [ ] One test for each of the 29 rows. The test name starts with the row ID
- [ ] Multi-viewer rows use two browser contexts: NAV-06
- [ ] Email comes from the Mailpit helper: LOGIN-08, INV-02
- [ ] LOGIN-08 needs an expired Email Verification Key. Set its time with the timestamp helper
- [ ] W-29 is not a defect. NAV-08 expects the version of the client under test: "HootDraft v2.4.0" on AngularJS and "HootDraft v3.0.0" on the new client
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
