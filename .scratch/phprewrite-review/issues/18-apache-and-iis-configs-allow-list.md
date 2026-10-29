# 18: The Apache and IIS configs only allow `api/index.php` and static assets

**What to build:** On Apache and IIS, as with the Docker nginx config, only the API front controller, the built Angular app and static assets can be reached. Spec 2g says: "Prefer a whitelist (only `api/index.php` and static assets reachable) over the current blacklist." `.htaccess` and `web.config` are still deny lists. Known Compromises only says they weren't tested, not that they stayed deny lists.

This is marked for a human because nothing in the Docker box runs Apache or IIS to check the result.

**Blocked by:** None (can start immediately).

**Status:** ready-for-human

- [ ] `.htaccess` and `web.config` serve `api/index.php` for `/api/*`, the app's entry page and its static assets, and nothing else (for example `src/`, `config/`, `var/`, `vendor/`, `.env*`, `composer.*`, `phinx.*`, `deploy/`, `db/`)
- [ ] `EXAMPLE_nginx` matches the Docker nginx allow list
- [ ] Each config has been checked on a real Apache or IIS server, or the Known Compromises "Not verified" line still says it hasn't
