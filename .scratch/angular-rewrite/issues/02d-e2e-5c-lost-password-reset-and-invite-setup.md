# 02d: E2E tests for 5C Lost password, Reset, and invite setup

**What to build:** One E2E test for each row of route group 5C in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 17 rows: LOST-01 to LOST-05, RESET-01 to RESET-06, INVITE-01 to INVITE-06. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** ready-for-agent

- [ ] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-18 (RESET-04, INVITE-04, LOST-04), W-32 (RESET-03, INVITE-03)
- [ ] One test for each of the 17 rows. The test name starts with the row ID
- [ ] The Reset Key and the invite link come from the Mailpit helper: LOST-02, and the RESET and INVITE rows
- [ ] The reCAPTCHA uses the public test keys of the E2E stack: LOST-01, LOST-02, LOST-04
- [ ] W-32: the rows RESET-03 and INVITE-03 name the new text, with "Sauron"
- [ ] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [ ] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [ ] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [ ] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group
