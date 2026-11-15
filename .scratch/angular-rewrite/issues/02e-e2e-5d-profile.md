# 02e: E2E tests for 5D Profile

**What to build:** One E2E test for each row of route group 5D in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 10 rows: PROFILE-01 to PROFILE-10. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-01 (PROFILE-09), W-06 (PROFILE-02)
- [ ] One test for each of the 10 rows. The test name starts with the row ID
- [ ] PROFILE-05 reads the email at the new address from the Mailpit helper
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
