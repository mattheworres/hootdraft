# 02m: E2E tests for 7A Create and edit a Draft

**What to build:** One E2E test for each row of route group 7A in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 25 rows: CREATE-01 to CREATE-15, EDIT-01 to EDIT-10. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-01 (CREATE-12, EDIT-09), W-02 (EDIT-10), W-15 (EDIT-07), W-17 (CREATE-10, EDIT-04), W-23 (CREATE-07)
- [ ] One test for each of the 25 rows. The test name starts with the row ID
- [ ] Confirm on the running client what the row states with "(to confirm)", then correct the row and remove the note: EDIT-07
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
