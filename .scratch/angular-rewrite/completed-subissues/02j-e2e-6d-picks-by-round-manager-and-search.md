# 02j: E2E tests for 6D Picks by Round, Picks by Manager, and search

**What to build:** One E2E test for each row of route group 6D in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 22 rows: ROUND-01 to ROUND-06, MANAGER-01 to MANAGER-06, SEARCH-01 to SEARCH-10. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** awaiting-human-review

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-12 (ROUND-06, MANAGER-06, SEARCH-10), W-21 (SEARCH-08), W-24 (ROUND-05)
- [x] One test for each of the 22 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: ROUND-06, MANAGER-06, SEARCH-10
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers

## Comments

- Known failures: ROUND-01, MANAGER-01, SEARCH-01 (W-06, the same as BOARD-01), ROUND-05 (W-24, W-43), SEARCH-02 (W-41), ROUND-06, MANAGER-06, SEARCH-10 (W-12), SEARCH-08 (W-21, W-41), MANAGER-02, MANAGER-03, MANAGER-04 (W-42), MANAGER-05 (W-43).
- Added to the list after the tests: W-41 (the Position and Team lists show a blank option, not "(all positions)" and "(all teams)", at load and after **Clear**), W-42 (the Picks request of a Manager gives no Manager name), W-43 (the Round Picks and the Picks of a Manager are requested twice on load, so the toast shows twice).
