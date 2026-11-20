# 02g: E2E tests for 6A Draft loading, Draft Password, and Draft navigation

**What to build:** One E2E test for each row of route group 6A in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 25 rows: DRAFT-01 to DRAFT-13, PW-01 to PW-12. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-07 (DRAFT-11), W-08 (PW-01), W-09 (PW-09), W-10 (PW-10)
- [x] One test for each of the 25 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: PW-11
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
