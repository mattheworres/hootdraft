# 02c: E2E tests for 5B Register and email link

**What to build:** One E2E test for each row of route group 5B in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 13 rows: REG-01 to REG-09, VERIFY-01 to VERIFY-04. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect". W-18 does not hold for REG-07, so REG-07 has no "Known defect", and W-18 no longer lists it (approved by the maintainer, 2026-11-16). W-06 applies to REG-09 and VERIFY-04
- [x] One test for each of the 13 rows. The test name starts with the row ID
- [x] The Email Verification Key comes from the Mailpit helper: REG-02, VERIFY-01, VERIFY-02
- [x] The reCAPTCHA uses the public test keys of the E2E stack: REG-01, REG-02, REG-07
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client, with only the known failures of this group. Run the full suite (`npm test` in `e2e/`) to check: it runs each of the four browsers
- [x] Approved by the maintainer (2026-11-16): a permanent fix for the missing file mounts in both nginx containers (the developer box and the E2E stack). Each container mounts directories, not single files: `docker/nginx/` at `/etc/nginx/conf.d`, and `e2e/stack/nginx/` (with `client-config.conf` and `client-config.js`) at `/etc/nginx/hootdraft`. Git and editors replace a file on disk, and a replaced file vanishes from a single-file mount
- [x] Maintainer asked to include Typescript files in `check-terms`
- [x] Maintainer also added `test-ui` script so that humans can use Playwright's UI mode. `/e2e/README.md` also updated.

## Comments

### 2026-11-16: implemented, awaiting review

Each item is done. The 13 tests are in `e2e/tests/5b-register.spec.ts`. They pass in Chromium, Firefox, WebKit, and Edge on the AngularJS client. The two known failures (REG-09, VERIFY-04) fail on the URL assertion. The full suite passes in all four browsers (185 tests). The CodeScene safeguard passes, and the new file scores 10.0. The GitHub Actions workflow has not run.

Decisions for the review:

- **W-18 does not hold for REG-07.** On AngularJS, **Register** is disabled after a failed register. `vcRecaptchaService.reload()` broadcasts `reCaptchaReset`, and `angular-recaptcha` then sets the `recaptcha` validity of the form to false, so `registerFormIsInvalid()` returns true. REG-07 asserts the disabled button and passes, with no "Known defect". REG-07 and the sentence about it are removed from W-18. The Decision column is unchanged. The maintainer approved this edit (2026-11-16). LOST-04 is still a real defect: its button checks only `lostInProgress`.
- **W-06 on REG-09 and VERIFY-04.** Both rows had "the same toast and redirect as LOGIN-13", which is a "previous route" result. Ticket 02 says W-06 applies to each such row. Both now start on a Draft screen and are known failures with "Known defect" W-06. VERIFY-04 checks "no request to the email link API" before the URL assertion, so the check also runs on AngularJS.
- **Templates.** Four `data-testid` attributes on `register.component.html`: `register-name`, `register-password`, `register-confirm-password`, and `register-show-password`. The labels have `for` but the inputs have no `id`, so the labels name nothing. **Email** uses its role and placeholder name, the same as on `/login`. `register-name` is arguable: its placeholder "Your name (for display)" does name it, but the new client's label will be "Name".
- **reCAPTCHA.** The checkbox is found by the iframe title "reCAPTCHA" (exact), because a Google iframe takes no `data-testid`.
- **VERIFY-02** has two steps: a wrong key and a used key.
- **Environment.** The E2E nginx container had lost its bind mount again (`/js/dev.js` gave 404). `docker compose -f stack/docker-compose.yml up -d --force-recreate nginx` fixed it.
- **nginx mounts (approved 2026-11-16).** Both nginx containers now mount directories, not single files, so a file that git or an editor replaces no longer vanishes from the container: `docker/nginx/` at `/etc/nginx/conf.d`, and the new `e2e/stack/nginx/` (holding `client-config.conf` and `client-config.js`) at `/etc/nginx/hootdraft`. Recreate each nginx container once to pick this up. A real change to `default.conf` still needs `nginx -s reload`.

### 2026-11-16: review changes

- Code names use Email Verification in full, as GLOSSARY.md asks. `bin/check-terms` now scans the TypeScript of `e2e/`, not `app/`. It found 7 violations, all in this spec. The inventory row IDs VERIFY-01 to VERIFY-04 have an allow entry.
- Code that repeats three or more times is now in shared helpers, and each copy uses it:
  - `recordApiRequests` in `helpers/intercept.ts`: LOGIN-12, INV-05, VERIFY-04.
  - `expectAlreadyLoggedInToast` in `helpers/toast.ts`: LOGIN-13, REG-09, VERIFY-04.
  - `openDraftScreen` and `openDraftScreenLoggedIn` in the new `helpers/draft-screen.ts`: LOGIN-03, LOGIN-04, LOGIN-13, REG-09, VERIFY-04.
  - `emailFor` in `helpers/api.ts`: the setup helpers, INV-02, and this spec.
- A spec-local helper replaces code that repeats twice in this spec: `openEmailVerificationLink` (VERIFY-01, VERIFY-02, VERIFY-04). `emailField`, `workingModal`, and the held register response have one copy in each of the two spec files.
- `confirmField` is now `confirmPasswordField`, to match its `data-testid`.
- VERIFY-02 now also covers a wrong key.
- The Starting state of REG-09 and VERIFY-04 no longer repeats the step that opens the route.

### 2026-11-16: process changes from the retro

A `retro` of the 02c sessions changed the process for the next tickets. These changes are outside the scope of 02c:

- `CODING_STANDARDS.md`: E2E code that repeats becomes a helper (twice in one file: a local helper; three or more times: a shared helper).
- `docs/agents/issue-tracker.md`: tick a box only when the work matches its text, and a box that claims tests pass names the command and its result.
- `e2e/README.md`: run Chromium only while you change code, and each browser once before the work is done.
