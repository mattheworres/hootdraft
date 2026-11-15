# 02q: E2E tests for 7E Add a Trade

**What to build:** One E2E test for each row of route group 7E in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 10 rows: ADDTRADE-01 to ADDTRADE-10. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-01 (ADDTRADE-09), W-02 (ADDTRADE-10), W-22 (ADDTRADE-02), W-33 (ADDTRADE-08)
- [ ] One test for each of the 10 rows. The test name starts with the row ID
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
