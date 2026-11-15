# 02c: E2E tests for 5B Register and email link

**What to build:** One E2E test for each row of route group 5B in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 13 rows: REG-01 to REG-09, VERIFY-01 to VERIFY-04. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-18 (REG-07)
- [ ] One test for each of the 13 rows. The test name starts with the row ID
- [ ] The Email Verification Key comes from the Mailpit helper: REG-02, VERIFY-01, VERIFY-02
- [ ] The reCAPTCHA uses the public test keys of the E2E stack: REG-01, REG-02, REG-07
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
