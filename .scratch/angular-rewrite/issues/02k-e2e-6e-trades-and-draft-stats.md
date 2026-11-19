# 02k: E2E tests for 6E Trades and Draft Stats

**What to build:** One E2E test for each row of route group 6E in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 9 rows: TRADES-01 to TRADES-05, STATS-01 to STATS-04. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-14 (STATS-02), W-34 (STATS-03)
- [ ] One test for each of the 9 rows. The test name starts with the row ID
- [ ] Multi-viewer rows use two browser contexts: TRADES-05
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
