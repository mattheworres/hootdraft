# 02i: E2E tests for 6C Draft Board

**What to build:** One E2E test for each row of route group 6C in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 18 rows: BOARD-01 to BOARD-18. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-06 (BOARD-01). BOARD-03 was also corrected: each Manager keeps a column in a Serpentine Draft, and the Pick numbers reverse
- [x] One test for each of the 18 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: BOARD-13, BOARD-14, BOARD-15, BOARD-16, BOARD-17, BOARD-18
- [x] The Playwright clock controls the flip clock: BOARD-07, BOARD-08, BOARD-16. Do not wait in real time for a pick timer
- [x] Set the start time of the current Pick with the timestamp helper where a row needs time left or no time left
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers

## Comments

- W-40 is new (found by BOARD-15).
- BOARD-18 is the same cause as W-39 (a reset does not change the counter). It is added to the rows of W-39.
- Multi-viewer rows: the second viewer has its own browser context with its own clock. The Commissioner acts through the API setup helpers, as in 02h.
- Known failures: BOARD-01 (W-06), BOARD-15 (W-40), BOARD-18 (W-39).
