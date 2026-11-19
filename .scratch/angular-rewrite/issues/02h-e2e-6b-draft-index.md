# 02h: E2E tests for 6B Draft index

**What to build:** One E2E test for each row of route group 6B in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 17 rows: INDEX-01 to INDEX-17. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-24 (INDEX-11)
- [ ] The decision for these items is "Keep". The rows do not change: W-11 (INDEX-17)
- [ ] One test for each of the 17 rows. The test name starts with the row ID
- [ ] Multi-viewer rows use two browser contexts: INDEX-12, INDEX-13, INDEX-14, INDEX-15, INDEX-16, INDEX-17
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
