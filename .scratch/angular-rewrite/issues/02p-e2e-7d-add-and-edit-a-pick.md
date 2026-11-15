# 02p: E2E tests for 7D Add and edit a Pick

**What to build:** One E2E test for each row of route group 7D in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 25 rows: ADDPICK-01 to ADDPICK-15, EDITPICK-01 to EDITPICK-10. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-01 (ADDPICK-13, EDITPICK-09), W-02 (ADDPICK-14, EDITPICK-10), W-04 (EDITPICK-10), W-06 (EDITPICK-03), W-15 (EDITPICK-05), W-16 (ADDPICK-09, ADDPICK-10), W-19 (ADDPICK-12), W-31 (EDITPICK-06), W-33 (EDITPICK-07)
- [ ] One test for each of the 25 rows. The test name starts with the row ID
- [ ] W-31: EDITPICK-06 changes to a form with **Cancel**, which returns to the list of Picks
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
