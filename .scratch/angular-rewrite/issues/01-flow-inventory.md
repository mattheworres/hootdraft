# 01: Flow inventory

**What to build:** `docs/flow-inventory.md`, the definition of "100% of flows". See `AngularRewrite.md`, Section 2.3, item 1.

**Blocked by:** none

**Status:** ready-for-agent

- [ ] Read every route file, component, directive, and template in `app/features/`. Do not guess from route names
- [ ] One row for each route, for each viewer (anonymous, Commissioner, Admin), and for each Draft status (Setting Up, In Progress, Complete) where the screen changes
- [ ] Rows for each validation message and each error state (API error, 401, 403, 404, wrong Draft Password)
- [ ] Rows for multi-viewer flows: a second viewer sees a new Pick, an edited Pick, a Trade, a Draft status change, and the pick timer
- [ ] Rows for redirects and guards (a Commissioner route without a login, an Admin route as a Commissioner)
- [ ] Each row has: an ID, the route, the viewer, the starting state, the steps, the expected result, and empty columns for "test file", "passes on AngularJS", "migrated", and "known defect"
- [ ] Rows are grouped by route group. These groups become the pull requests of phases 5 to 8
- [ ] Use `GLOSSARY.md` terms only
- [ ] A list at the end of behavior that looks wrong in the AngularJS client, for the maintainer's decision
- [ ] The maintainer approves the inventory
