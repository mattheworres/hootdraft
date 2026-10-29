# 02: Release packages start up and can write their runtime folders

**What to build:** A release built with `dep package_release` and deployed with `dep deploy` answers requests, and can write sessions, logs and cache.

Today packaging runs `composer install` with dev dependencies and then deletes `vendor/phpunit` (and friends) to slim the archive. The generated `vendor/composer/autoload_files.php` still requires PHPUnit's `Functions.php`, so the first request fails while autoloading. Spec 2h asks for `composer install --no-dev -o`. Spec 3.10 asks for a writable session save path: the shared `var/sessions` and `var/log`, plus `var/cache`, are created by the deploy user and nothing makes them writable by PHP-FPM.

**Blocked by:** Human: I think this needs to wait until after the Angular frontend is ported over.

**Status:** wontfix

- [ ] The packaged `vendor/` comes from a `--no-dev -o` install, and the "remove spurious packages" step is gone or only removes what is genuinely unused
- [ ] Deployer still has the tools it needs at build time (Deployer itself, gulp and yarn), without them ending up in the production vendor tree
- [ ] Running the release's front controller with `vendor/` built the same way (for example `APP_ENV=prod php api/index.php`, or one request through the Docker box) gets past autoload
- [ ] The deploy makes `var/sessions`, `var/log` and `var/cache` writable by the web server user (Deployer's `writable_dirs` / `deploy:writable`, or equivalent)
- [ ] The two "Not verified" deploy notes in Known Compromises say what was checked and what still needs a real server
