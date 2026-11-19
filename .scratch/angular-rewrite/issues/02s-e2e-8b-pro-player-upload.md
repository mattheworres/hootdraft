# 02s: E2E tests for 8B Pro Player upload

**What to build:** One E2E test for each row of route group 8B in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 8 rows: PRO-01 to PRO-08. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-03 (PRO-07, PRO-08), W-17 (PRO-03)
- [ ] One test for each of the 8 rows. The test name starts with the row ID
- [ ] Add small CSV fixture files to `e2e/`: one valid, one that the API refuses (PRO-05), and one larger than 768 KB (PRO-04). The Pro Player list that loads once for each run must stay usable for the other tests
- [ ] Confirm on the running client what the row states with "(to confirm)", then correct the row and remove the note: PRO-04
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers
