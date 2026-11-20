# 02h: E2E tests for 6B Draft index

**What to build:** One E2E test for each row of route group 6B in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 17 rows: INDEX-01 to INDEX-17. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-24 (INDEX-11)
- [x] The decision for these items is "Keep". The rows do not change: W-11 (INDEX-17)
- [x] One test for each of the 17 rows. The test name starts with the row ID. INDEX-16 found a defect that is not in the W-items table: W-39
- [x] Multi-viewer rows follow PW-11: the Commissioner acts through the API setup helpers, because only the flow under test goes through the UI. These rows are INDEX-12 to INDEX-17
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client. Only the known failures fail: INDEX-11 (W-24) and INDEX-16 (W-39). Run `npx playwright test --project=chromium --project=helpers` in `e2e/`. CI runs the other three browsers

## Comments

- W-39 is new. A reset does not change the Draft counter, so the second viewer gets the Setting Up view with an empty Draft Order. The decision is to fix it, so INDEX-16 is marked with `knownFailureOnAngularJs('W-39')`.
- The "Upcoming Picks" error text never shows either: `draftInProgress.directive.js` does not bind `nextError`. No row covers it, so no item was added. W-24 names only the "Last 5 Picks" error.
