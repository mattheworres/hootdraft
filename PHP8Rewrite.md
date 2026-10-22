# HootDraft: PHP Rewrite

Given that HootDraft's underlying dependencies have been outdated for some time, combined with the fact that software vulnerabilities are being discovered and patched at a rate we have never seen thanks to LLMs - I've decided it's time to try and tackle the bitrot that has naturally set in here.

## Overarching Issues

A rudimentary look over the API code, while not revealing perfection, did reveal that most of the issues aren't necessarily in the code (though I will fix those). The issue is that packages/frameworks like Silex have been deprecated and untouched for many years now.

### Attack Plan

1. Generate a Docker developer box that stands up nginx with PHP 8.5, as well as a MariaDB database instance
2. Migrate the application internals & bootstrapping (now in `/old-api`, mostly in the `/config` folder) from Silex 2 to Symfony 8.1 micro-kernel - striving for latest version without hitting bleeding edge incompatability. Any sidesteps to avoid these incompatabilities can just be called out.
  a. (DONE) This includes all composer auto-loads, .gitignore pathing and the nginx
  b. (DONE) Upgrade to latest version of deployer
  c. (DONE) Reset composer for the new stack. Rewrite `composer.json` for PHP >= 8.4 (target is whatever the Docker box runs), dropping Silex, `symfony/security` 2.8, `cnam/security-jwt-service-provider`, Twig 2, Monolog 1, PHPUnit 7 and Phinx 0.10. Add `symfony/framework-bundle`, `symfony/runtime`, `symfony/security-bundle`, `symfony/dotenv`, `symfony/monolog-bundle`, and current `doctrine/dbal`, `phpmailer`, `google/recaptcha`, `phpfastcache`, `twig`, `phpunit`, `phinx`. Delete `composer.lock` and `vendor/` and regenerate with `docker compose exec php composer install`. Re-point PSR-4 to a single `PhpDraft\\` => `src/` mapping (plus `old-api/` kept under a temporary `PhpDraft\\Legacy\\` mapping so old code can be read and ported from, but never booted). Any dependency that can't reach a PHP 8.4+ compatible version gets called out here with its replacement.
  d. (DONE) Create the new skeleton at `/api` (the path nginx already serves): `api/index.php` as the front controller using `symfony/runtime`, a `Kernel` using `MicroKernelTrait`, `config/` (`bundles.php`, `services.yaml`, `routes.yaml`, `packages/*.yaml`), `src/` for ported code, and `var/cache` + `var/log` (both git-ignored). Goal of this step: `GET /api/` returns a JSON "hello" from the new kernel inside the Docker box, with no ported code yet.
  e. (DONE, except the service/repository/validator port, see Known Compromises) Replace the Silex bootstrapping pieces with Symfony equivalents, one `old-api/config/_*.php` file at a time:
     - `_app.php` / `bootstrap.php`: Kernel + `public` entry, `DEBUG_MODE` becomes `APP_DEBUG`
     - `_database.php`: DBAL connection as a service built from env vars
     - `_log.php`: Monolog bundle config, writing to `var/log`
     - `_register*.php` (Pimple): autowired services in `services.yaml`, with the `phpdraft.*` string keys replaced by class-name injection. Repositories, services and validators move to `src/` first as-is (namespace + constructor changes only) so later controller porting is mechanical
     - `_router.php`: `routes.yaml` / attribute routes, preserving every existing URL so the AngularJS client is unaffected. Produce a route inventory (method, path, controller, firewall, middleware) from the old router before touching it, and use it as the checklist for step 4
     - `_middlewares.php`: request listeners (the JSON body decode, `Content-Type` handling and the error handler returning `{"error": ...}`) and per-route checks (`draftViewable`, `draftInProgress`, `commishEditableDraft`, etc.) as `kernel.request` listeners or route-attribute-driven voters/listeners. Behavior is ported 1:1 here, redesign is out of scope
     - `_security.php` and `config/Security/*`: **stub only** in this step (a single firewall that permits everything on the new skeleton) because the real rewrite is step 3. Leave a clear TODO so nothing ships open
  f. (DONE) Config & secrets. Move `appsettings.php` constants (DB, mail, reCAPTCHA, auth key, debug) into `.env` / `.env.local` read by `symfony/dotenv`, with a committed `.env` of non-secret defaults and `.env.local` git-ignored. Wire the Docker box (`docker/.env`) to supply the DB values so the two stay in sync, and update `phinx.yml` to read the same env vars. Decide whether `appsettings.php` remains as a thin shim for the legacy code (preferred: delete it once step 4 finishes).
  g. (DONE) Paths & deny lists, everything that still references `api/bootstrap.php`: `.gitignore` (log + cache paths for `old-api/` and the new `api/var`), `docker/nginx/default.conf`, `EXAMPLE_nginx`, `vagrant-config/`, `web.config` and the Vagrant box (retire it in favor of Docker if no longer used). Prefer a whitelist (only `api/index.php` and static assets reachable) over the current blacklist.
  h. (DONE, not tested against a real server) Deployer (see 2b) must also package `api/`, `src/`, `config/`, `vendor/` correctly, run `composer install --no-dev -o`, warm the Symfony cache, run Phinx migrations, and point shared `.env.local` / `var/log` at the release symlink. Do not run a deploy from this branch until this and 2g are done.
  i. (DONE, the Silex-era tests are parked in tests/legacy until step 4) Tests. Keep `tests/` working: update `phpunit.xml` and the bootstrap/autoload for PHPUnit 11+, fix the PHP 8 breakages in the existing `tests/api/Domain/**` tests (they target classes that are moved in 2c/2e), and add one smoke test that boots the kernel and hits `/api/`. Run in the Docker box (`docker compose exec php vendor/bin/phpunit`).
  j. (DONE for migrations; the repositories' query style is a step 4 item) Database. Confirm the existing Phinx migrations run cleanly from scratch on MariaDB 12.3 inside the Docker box and that the DBAL upgrade (2.5 to current) doesn't break the repositories' query style (deprecated `fetchAll`/`executeQuery` calls are the likely offenders). Fix issues in migrations by adding new ones rather than editing the old ones.
  k. Done when: composer installs clean on PHP 8.4+, the kernel boots in the Docker box, `/api/` responds, tests run, migrations apply to a fresh DB, deploy config is updated, and nothing in the repo references `api/bootstrap.php`, Silex, or the 2.x/1.x versions above. Every sidestep or version compromise made along the way is listed under a "Known Compromises" heading at the bottom of this file.
3. Shore up user authentication
  a. Rely on Symfony built-ins as much as possible
  b. Move from JWT to just cookie based auth, which removes an open XSS attack surface on the client.
  c. Make the smallest changes on the AngularJS side, as I will approach an Angular rewrite later.
4. Begin porting code over, controller at a time
  a. While porting code over, any references to `PhpDraft` (case-insensitive) can be ported to the equivalent `HootDraft`.
5. After first pass of porting is complete, re-assess state of API:
  a. ensure deny lists are up to date

## Known Compromises

Things decided or sidestepped during step 2. Revisit as noted.

- **Services, repositories and validators were not moved out of `old-api/` in step 2.** Their constructors take the Silex `$app` and look dependencies up by `phpdraft.*` string key, so they can't run under the new kernel until each is converted to constructor injection. That conversion happens per controller in step 4 (the route inventory, `docs/route-inventory.md`, is the checklist). The `RouteCheck` guard infrastructure is in place, but the concrete checks (`draftViewable` etc.) need the ported validators, so they arrive with step 4 too.
- **No `Legacy` autoload mapping.** The old classes keep their original `PhpDraft\...` namespaces, which collide with the new `PhpDraft\` => `src/` mapping, so `old-api/` is read-only reference material, never booted. Port by copying into `src/` and fixing namespaces.
- **Native PDO instead of DBAL for queries.** `doctrine/dbal` 4 removed fetch-into-class, `Statement::fetch()` and `setFetchMode()`, which the ~100 prepared statements in the old repositories rely on. The `PDO` service hands them DBAL's own native connection, so the repositories can be ported mostly as-is. Move to DBAL's `fetchAssociative()`/hydration later if desired (also `fetchColumn` x2 and `exec` x1 need touching).
- **Security is a deny-by-default stub** (`config/packages/security.yaml`): no authenticator exists, so `/admin` and `/commish` are unreachable. Step 3 replaces it. `AUTH_KEY`, `AUTH_SECONDS` and `AUTH_KEY_HEADER` were intentionally dropped from the settings because JWT goes away.
- **Error handler behavior change:** HTTP exceptions keep their status (404, 403, ...) instead of everything becoming 500, and unexpected errors only reveal their message when `APP_DEBUG=1`. The old handler returned the raw exception message with a 500 always.
- **Symfony project root is the repo root** (`config/`, `src/`, `var/`, `bin/` beside the Angular app); only `api/index.php` is a web entry point, and the web server whitelists what is reachable.
- **Settings moved from `appsettings.php` to `.env` / `.env.local`.** The local `appsettings.php`, `phinx.yml` and `deploy.php` are still ignored by git and untouched; regenerate them with `dep setup` (or copy `deploy/deploy.php.release`) for the new formats. `deploy.php` must be rewritten for Deployer 8 (`host()->set('remote_user', ...)`, no `stage()`, no password option).
- **Phinx is now a runtime dependency** (needed on the server to migrate) and the bundled `deploy/composer.phar` and `deploy/phinx.phar` (2019-era) were removed; the server must have `composer` and PHP 8.4+.
- **Vagrant was removed** (Ubuntu 18.04 / PHP 7.2) in favor of the Docker box.
- **Not verified:** a real `dep deploy` against a server, `dep setup`/`package_release` (needs 7-Zip, yarn, gulp), the Apache `.htaccess` and IIS `web.config` rewrites, and the `EXAMPLE_nginx` config. Only the Docker nginx config, the Symfony kernel, tests and Phinx migrations were run. `dep list` and the `deploy` task tree load correctly.
