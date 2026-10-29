# 19: Rename the leftover `phpdraft` identifiers in the deploy and Docker config

**What to build:** Spec 4a says `PhpDraft` references can become `HootDraft`. Three places still use `phpdraft`: the `{phpdraft.*}` placeholders in the CI env template, the `phpdraft` database name and user in the Docker env example, and the `phpdraft:*` Deployer task names. Rename them to `hootdraft`. This changes the `dep phpdraft:*` commands you type.

The draft password header `X-PhpDraft-DraftPassword` stays as it is: the AngularJS client sends it, and Known Compromises 4.1 covers it.

**Blocked by:** 02 (same deploy files)

**Status:** ready-for-agent

- [ ] Deployer task names, the CI env template placeholders and the Docker env example use `hootdraft`
- [ ] `dep list` loads and shows the renamed tasks, and `deploy` still runs them in the same order
- [ ] The Docker README says how to move an existing local database (or that a fresh volume is needed) if the database name changes
- [ ] A search of the repo outside `vendor/`, `node_modules/` and git history finds `phpdraft` only in the draft password header, the migrations and the changelog-style docs
