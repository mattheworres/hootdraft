# 02: E2E stack, CI, and the suite on AngularJS

**What to build:** The Playwright suite in `e2e/`, its Docker stack, and the GitHub Actions workflow. Every row of `docs/flow-inventory.md` gets a test that passes on the AngularJS client. See `AngularRewrite.md`, Sections 2.3 and 2.4.

**Blocked by:** 01

**Status:** ready-for-agent

**Note:** This ticket is split. Do the work in the sub-tickets. This ticket holds the rules that they share, and it is complete when each sub-ticket is complete.

## Sub-tickets

Ticket 02a comes first. Tickets 02b to 02w are blocked by 02a only, so they can run in any order. When a sub-ticket is `completed`, move its file to `../completed-subissues/`. This ticket stays in `issues/` until each sub-ticket is there.

| Ticket | Scope | File |
| --- | --- | --- |
| 02a | E2E stack, helpers, and CI | `02a-e2e-stack-helpers-and-ci.md` |
| 02b | 5A Login, session, and navigation | `02b-e2e-5a-login-session-and-navigation.md` |
| 02c | 5B Register and email link | `02c-e2e-5b-register-and-email-link.md` |
| 02d | 5C Lost password, Reset, and invite setup | `02d-e2e-5c-lost-password-reset-and-invite-setup.md` |
| 02e | 5D Profile | `02e-e2e-5d-profile.md` |
| 02f | 5E Home | `02f-e2e-5e-home.md` |
| 02g | 6A Draft loading, Draft Password, and Draft navigation | `02g-e2e-6a-draft-loading-draft-password-and-navigation.md` |
| 02h | 6B Draft index | `02h-e2e-6b-draft-index.md` |
| 02i | 6C Draft Board | `02i-e2e-6c-draft-board.md` |
| 02j | 6D Picks by Round, Picks by Manager, and search | `02j-e2e-6d-picks-by-round-manager-and-search.md` |
| 02k | 6E Trades and Draft Stats | `02k-e2e-6e-trades-and-draft-stats.md` |
| 02l | 6F Depth Chart | `02l-e2e-6f-depth-chart.md` |
| 02m | 7A Create and edit a Draft | `02m-e2e-7a-create-and-edit-a-draft.md` |
| 02n | 7B Managers and Draft status changes | `02n-e2e-7b-managers-and-draft-status-changes.md` |
| 02o | 7C Round Times | `02o-e2e-7c-round-times.md` |
| 02p | 7D Add and edit a Pick | `02p-e2e-7d-add-and-edit-a-pick.md` |
| 02q | 7E Add a Trade | `02q-e2e-7e-add-a-trade.md` |
| 02r | 8A Users | `02r-e2e-8a-users.md` |
| 02s | 8B Pro Player upload | `02s-e2e-8b-pro-player-upload.md` |
| 02t | 8C Regenerate Draft Stats | `02t-e2e-8c-regenerate-draft-stats.md` |
| 02u | Screenshot report tool | `02u-screenshot-report-tool.md` |
| 02v | API: retry a transaction after a deadlock | `02v-api-deadlock-retry.md` |
| 02w | CI speed and runner minutes | `02w-ci-speed-and-runner-minutes.md` |

## Rules for each sub-ticket

- Locators use role and label first, then `data-testid` where role and label are weak. Visible text is for assertions only. No `ng-*` attributes, no CSS structure, and no existing `id` values
- Edits to `app/` templates add `data-testid` attributes only. Each attribute name describes the element with `GLOSSARY.md` terms, for example `pick-row`
- Each test creates its state through the API setup helpers. Only the flow under test goes through the UI
- Multi-viewer tests use two browser contexts
- The Playwright clock controls client timers. Only the timestamp helper writes test state to the E2E database. The database reset is not test state
- The "Decision" column of the list at the end of the inventory is the maintainer's approval. For an approved fix, change the rows to the correct result, fill in "Known defect" with the item number, and mark the test as a known failure on AngularJS. The AngularJS code is not fixed, except for the fixes in the exceptions below
- W-04 and W-06 name "each" row with a result, not only the rows in their list. Apply them to each row of your group with that result: a save that the API refuses with an error in an array (W-04), and a "previous route" result (W-06)
- W-28 is "Keep": a toast has no title unless the row names one
- If a test shows a defect that the list does not hold, add an item to the list with a proposal and stop work on that row. Only the maintainer fills in "Decision"
- If the running client differs from a row and the difference is not a defect, correct the row. An ID never changes

## Accepted exceptions

The maintainer accepted these fixes in the AngularJS code, with changes beyond `data-testid` attributes. They are W-37 (`customSmartTable.filter.js`) and W-38 (`persistTableState.directive.js`) in the inventory, found by the tests of HOME-05 and HOME-12. Other sub-tickets still do not fix the AngularJS code without the maintainer's approval.

## Done when

- [ ] Each sub-ticket is complete
- [ ] Each of the 316 inventory rows has "Test file" and "Passes on AngularJS" filled in
- [ ] The full suite passes on the AngularJS client in all four browsers in CI, with only the approved known failures
