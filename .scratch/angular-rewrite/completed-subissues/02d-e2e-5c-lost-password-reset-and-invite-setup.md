# 02d: E2E tests for 5C Lost password, Reset, and invite setup

**What to build:** One E2E test for each row of route group 5C in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 17 rows: LOST-01 to LOST-05, RESET-01 to RESET-06, INVITE-01 to INVITE-06. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-18 (RESET-04, INVITE-04, LOST-04), W-32 (RESET-03, INVITE-03), W-06 (LOST-05, RESET-06, INVITE-06: each "previous route" row)
- [x] One test for each of the 17 rows. The test name starts with the row ID
- [x] The Reset Key and the invite link come from the Mailpit helper: LOST-02, and the RESET and INVITE rows
- [x] The reCAPTCHA uses the public test keys of the E2E stack: LOST-01, LOST-02, LOST-04
- [x] W-32: the rows RESET-03 and INVITE-03 name the new text, with "Sauron"
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group: `npm test` in `e2e/` passed, 253 tests

## Comments

### 2026-11-16: implemented, awaiting review

The 17 tests are in `e2e/tests/5c-lost-reset-invite.spec.ts`. `npm test` in `e2e/` passed: 253 tests in Chromium, Firefox, WebKit, and Edge, with only the known failures (LOST-04, LOST-05, RESET-03, RESET-04, RESET-06, INVITE-03, INVITE-04, INVITE-06). `composer check` passes (316 tests). The CodeScene safeguard passes and the spec scores 10.0. The GitHub Actions workflow has not run. Not committed: commits are blocked.

Decisions for the review:

- **W-18 on LOST-04, RESET-04, INVITE-04.** The rows now say the button is disabled, so no request is sent. The tests click the disabled button with `force` to prove it. LOST-04 no longer tests the API's captcha toast: the test keys always pass.
- **W-06 on LOST-05, RESET-06, INVITE-06.** Each is a "previous route" result, so each starts on a Draft screen and is a known failure.
- **W-32 on RESET-03, INVITE-03.** Both name the "Sauron" sentence. Each has two steps: a wrong key and a used key. The Sauron check is last and soft, so the other checks and the second step still run on AngularJS.
- **Templates.** `data-testid` on the password, confirm, and Show controls of both screens (inputs have no `id`, so labels name nothing), the email text, and the invite bad-key email.
- **Helpers.** New `helpers/recaptcha.ts` (moved from 5b, which now imports it), `helpers/form.ts` (the Email, Password, and Confirm validation checks that 5A, 5B, and 5C shared), `linkPath` and `withWrongKey` in `helpers/mailpit.ts`, and `ApiSetup.requestPasswordReset`, `resetPassword`, `inviteUser`. All are in `e2e/README.md`.
