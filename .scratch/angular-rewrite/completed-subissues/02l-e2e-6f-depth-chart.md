# 02l: E2E tests for 6F Depth Chart

**What to build:** One E2E test for each row of route group 6F in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 11 rows: DEPTH-01 to DEPTH-11. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-24 (DEPTH-11), W-30 (DEPTH-09)
- [x] One test for each of the 11 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: DEPTH-10
- [x] DEPTH-06 and DEPTH-07 set the time that the Draft became Complete with the timestamp helper
- [x] Confirm on the running client what the row states with "(to confirm)", then correct the row and remove the note: DEPTH-09
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
