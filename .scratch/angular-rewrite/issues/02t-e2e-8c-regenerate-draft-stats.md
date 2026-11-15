# 02t: E2E tests for 8C Regenerate Draft Stats

**What to build:** One E2E test for each row of route group 8C in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 8 rows: ADMSTATS-01 to ADMSTATS-08. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-03 (ADMSTATS-07, ADMSTATS-08), W-27 (ADMSTATS-05)
- [ ] One test for each of the 8 rows. The test name starts with the row ID
- [ ] Confirm on the running client what the row states with "(to confirm)", then correct the row and remove the note: ADMSTATS-05
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
