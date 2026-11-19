# 02f: E2E tests for 5E Home

**What to build:** One E2E test for each row of route group 5E in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 16 rows: HOME-01 to HOME-16. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** awaiting-human-review

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-24 (HOME-16), W-25 (HOME-02, HOME-07, HOME-11)
- [x] One test for each of the 16 rows. The test name starts with the row ID
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers

## Notes for the maintainer

- HOME-05 was blocked by W-37 (the Commissioner filter threw a `TypeError`: the API sends numeric IDs, the filter called `toLowerCase()`). Fixed in AngularJS in `customSmartTable.filter.js` with a string comparison and a comment that the new client uses typed IDs and `===`. HOME-05 now passes. W-27 (Admin Draft Stats) is not the same cause: it has no column binding.
- HOME-12 was blocked by W-38 (the table showed page 1 again after a visit to a Draft). Cause: smart-table resets the page when the Draft array is replaced, and the Drafts load after the saved state. Fixed in AngularJS in `persistTableState.directive.js`: it sets the saved page again once the Drafts arrive. HOME-12 passes.
- HOME-08: the test does not check that **First** and **Last** show with more than 7 pages. That needs over 105 Drafts. It checks that they are absent with 2 pages.
- Observation, not an item: the text in the search box "Search Leagues..." does not come back after a visit to a Draft. HOME-12 names the filter, the sort, and the page only.
- Added `setDraftCreateTime` to `e2e/helpers/timestamps.ts` and to the README.
