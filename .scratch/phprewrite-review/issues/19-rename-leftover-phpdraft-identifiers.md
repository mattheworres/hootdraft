# 19: Rename the leftover `phpdraft` identifiers in the deploy and Docker config

**What to build:** Spec 4a says `PhpDraft` references can become `HootDraft`. Three places still use `phpdraft`: the `{phpdraft.*}` placeholders in the CI env template, the `phpdraft` database name and user in the Docker env example, and the `phpdraft:*` Deployer task names. Rename them to `hootdraft`. This changes the `dep phpdraft:*` commands you type.

The draft password header `X-PhpDraft-DraftPassword` stays as it is: the AngularJS client sends it, and Known Compromises 4.1 covers it.

**Blocked by:** 02 (same deploy files)

**Status:** completed

- [x] Deployer task names, the CI env template placeholders and the Docker env example use `hootdraft`
- [x] `dep list` loads and shows the renamed tasks, and `deploy` still runs them in the same order
- [x] The Docker README says how to move an existing local database (or that a fresh volume is needed) if the database name changes
- [x] A search of the repo outside `vendor/`, `node_modules/` and git history finds `phpdraft` only in client-related areas or changes. These will be changed later.

## Comments

**Implementer (agent):**

- The old `phpdraft` names also go away in the Deployer setting keys (`hootdraft_rollback_required`, `hootdraft_setup_variables`, `get('hootdraft')`), the default answers in the wizards (`~/hootdraft_settings`, `~/hootdraft_releases`, database name `hootdraft`) and the committed root `.env` (`DB_NAME`, `DB_USER`).
- `hootdraft:rollback` reads `hootdraft_rollback_required` with a default of `false`. An old `deploy.php` sets only `phpdraft_rollback_required`, so without the default a failure before `hootdraft:set_rollback` can throw in the fail handler.
- Users who made a settings backup with the old wizard must type `~/phpdraft_settings` when `dep import` asks for the path.
- `dep tree deploy`, `dep tree package_release` and `dep tree setup` give the same tasks in the same order as before, with only the names changed.
- The search finds `phpdraft` in more places than the original last criterion listed. The maintainer changed the criterion to accept these places and ticked it. The places that stay are:
  - The AngularJS module names (`phpdraft`, `phpdraft.env` and the others) in `app/`, `gulp/`, `.eslintrc` and `deploy/config.js.ci`. The client code needs these names. Change them only with the client.
  - External URLs that contain the old repository name or social handle: the badges and links in `README.MD`, and the Twitter link in `templates/email/UserInvite.html`.
  - The draft password header: `DRAFT_PASSWORD_HEADER` in `.env` and `HTTP_X_PHPDRAFT_DRAFTPASSWORD` in `tests/Functional/{DraftRead,Manager,Pick}Test.php`.
  - The upgrade section in `docker/README.md`, which names the old database.
  - `PHP8Rewrite.md` (the spec) and the `.scratch/` issues.
  - The migrations contain no `phpdraft`.
