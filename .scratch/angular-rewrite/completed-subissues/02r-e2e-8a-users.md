# 02r: E2E tests for 8A Users

**What to build:** One E2E test for each row of route group 8A in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 14 rows: USERS-01 to USERS-14. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-03 (USERS-12, USERS-13), W-17 (USERS-08), W-26 (USERS-09, USERS-10)
- [x] One test for each of the 14 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: USERS-11
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
