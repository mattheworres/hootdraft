# 09: Cutover

**What to build:** Remove the AngularJS client and serve the new client. See `AngularRewrite.md`, Section 3, phase 9.

**Blocked by:** 06, 07, 08

**Status:** ready-for-agent

- [ ] Every inventory row is marked as migrated
- [ ] Run the screenshot report for the full set of screens once, for the maintainer's review, before any deletion
- [ ] Delete `app/`, `gulp/`, `gulpfile.babel.js`, `.eslintrc`, the root `package.json` and `yarn.lock` (or reduce them to what `e2e/` needs), and the built `js/`, `css/`, `fonts/` and `index.html` rules that no longer apply
- [ ] nginx (`docker/nginx/default.conf`, `EXAMPLE_nginx`) serves the new build output, with the HTML5 URL fallback to `index.html`
- [ ] The Deployer tasks in `deploy/` check for, package, and upload the new build output
- [ ] The E2E suite targets one client. Remove the client setting, the known-failure marks, and the "passes on AngularJS" column
- [ ] `README.MD` and `docker/README.md` describe the new build
- [ ] The repository has no AngularJS code, and the full suite passes in four browsers in CI
