# Coding standards

Rules for the reviewer. Mechanical rules live in checks instead: `composer check` runs PHPStan and the glossary term check (`bin/check-terms`).

## Tests

### Tests never change the shared test database schema

Do not roll back, migrate down, or alter tables from a test. All Functional tests share one database, so a test that changes its schema breaks every other test.

**Why:** a migration test rolled the `users` table back with `down()`. MariaDB refused to drop the columns ("Row size too large") and left the table half rolled back. Every later test run failed until someone repaired the table as root.

**How to apply:** flag a test that calls Phinx `rollback()` or `migrate()`, or runs `ALTER`, `DROP` or `CREATE` on the shared database. Test a migration's data logic on a scratch database instead, or check it by hand against a copy of real data.
