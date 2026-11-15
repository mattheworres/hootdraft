# 02o: E2E tests for 7C Round Times

**What to build:** One E2E test for each row of route group 7C in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 13 rows: TIMES-01 to TIMES-13. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-01 (TIMES-11), W-02 (TIMES-12), W-06 (TIMES-03), W-14 (TIMES-06), W-18 (TIMES-09), W-20 (TIMES-08), W-24 (TIMES-13), W-34 (TIMES-08)
- [ ] One test for each of the 13 rows. The test name starts with the row ID
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
