# 01: Flow inventory

**What to build:** `docs/flow-inventory.md`, the definition of "100% of flows". See `AngularRewrite.md`, Section 2.3, item 1.

**Blocked by:** none

**Status:** completed

- [x] Read every route file, component, directive, and template in `app/features/`. Do not guess from route names
- [x] One row for each route, for each viewer (anonymous, Commissioner, Admin), and for each Draft status (Setting Up, In Progress, Complete) where the screen changes
- [x] Rows for each validation message and each error state (API error, 401, 403, 404, wrong Draft Password)
- [x] Rows for multi-viewer flows: a second viewer sees a new Pick, an edited Pick, a Trade, a Draft status change, and the pick timer
- [x] Rows for redirects and guards (a Commissioner route without a login, an Admin route as a Commissioner)
- [x] Each row has: an ID, the route, the viewer, the starting state, the steps, the expected result, and empty columns for "test file", "passes on AngularJS", "migrated", and "known defect"
- [x] Rows are grouped by route group. These groups become the pull requests of phases 5 to 8
- [x] Use `GLOSSARY.md` terms only
- [x] A list at the end of behavior that looks wrong in the AngularJS client, for the maintainer's decision
- [x] The maintainer approves the inventory

## Comments

**2026-11-14, agent:** `docs/flow-inventory.md` is written: 316 rows in 19 route groups, and a list of 34 items (W-01 to W-34) for the maintainer's decision.

- Each row states what the AngularJS client does today. No row asserts a fix. When the maintainer approves an item, change its rows to the correct result and fill in "Known defect".
- The rows come from the code in `app/features/` and `src/`, not from a running client. Four rows have the note "(to confirm)": DEPTH-09, EDIT-07, PRO-04, ADMSTATS-05.
- The largest findings: the client has no route guards (W-01 to W-03), Picks by Round, Picks by Manager, and search never refresh for a second viewer (W-12), and "previous route" is `/home` in most cases (W-06).
