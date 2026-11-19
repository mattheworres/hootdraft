# Coding standards

Rules for the reviewer. Mechanical rules live in checks instead: `composer check` runs PHPStan and the glossary term check (`bin/check-terms`).

## Tests

### Tests never change the shared test database schema

Do not roll back, migrate down, or alter tables from a test. All Functional tests share one database, so a test that changes its schema breaks every other test.

**Why:** a migration test rolled the `users` table back with `down()`. MariaDB refused to drop the columns ("Row size too large") and left the table half rolled back. Every later test run failed until someone repaired the table as root.

**How to apply:** flag a test that calls Phinx `rollback()` or `migrate()`, or runs `ALTER`, `DROP` or `CREATE` on the shared database. Test a migration's data logic on a scratch database instead, or check it by hand against a copy of real data.

### E2E code that repeats becomes a helper

Code that repeats twice in one spec file becomes a helper in that file. Code that repeats three or more times anywhere in `e2e/` becomes a shared helper in `e2e/helpers/`, and every copy uses it. `e2e/README.md` documents each shared helper.

**Why:** the 5B spec copied locators and setup from 5A and left a note to move them "when 5C adds more copies". Each later spec would have added another copy.

**How to apply:**

- **Authoring:** before you write a locator, a setup sequence, or an assertion sequence in a spec, read `e2e/README.md` and grep `e2e/helpers/` for it. If a helper exists, use it. If it nearly fits, extend the helper.
- **Review:** count the copies of a locator, a setup sequence, or an assertion across the spec files of the diff and the existing specs. Flag a third copy that is not a shared helper, and a shared helper that some copies do not use.

### E2E results come from a whole-suite Chromium run

Report E2E tests as passing only after a run of the whole suite in Chromium, with the checks of the helpers: `npx playwright test --project=chromium --project=helpers` in `e2e/`, with no `-g` or file filter. CI runs Firefox, WebKit, and Edge on a pull request.

**Why:** a run of one spec looks green and skips the rest. The suite grows with each route group, so the agent runs one browser and CI runs the others.

**How to apply:**

- **Authoring:** run the whole suite in Chromium before you hand over. A failure that only CI shows in another browser is fixed after the CI run.
- **Review:** read `e2e/test-results/results.json`. Check that the run started after the last change to the staged files, that the Chromium project ran the new tests and the rest of the suite, and that the only failures are known failures.
