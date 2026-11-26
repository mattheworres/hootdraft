# 02k: E2E tests for 6E Trades and Draft Stats

**What to build:** One E2E test for each row of route group 6E in `docs/flow-inventory.md`, passing on the AngularJS client. The group has 9 rows: TRADES-01 to TRADES-05, STATS-01 to STATS-04. Follow the rules in ticket 02.

**Blocked by:** 02a

**Status:** completed

- [x] First, apply the approved items to the rows of this group. Change each row to the correct result and fill in "Known defect": W-14 (STATS-02), W-34 (STATS-03)
- [x] One test for each of the 9 rows. The test name starts with the row ID
- [x] Multi-viewer rows use two browser contexts: TRADES-05
- [x] A row with a "Known defect" value asserts the correct result and is marked as a known failure on AngularJS
- [x] Add `data-testid` attributes to the `app/` templates of this group only where role and label are weak
- [x] Fill in "Test file" and "Passes on AngularJS" for each row of the group
- [x] The tests of this group pass in Chromium on the AngularJS client, with only the known failures of this group. CI runs the other three browsers. `npx playwright test --project=chromium --project=helpers` in `e2e/`: 194 passed, with the 4 known failures (TRADES-01, STATS-01, STATS-02, STATS-03) failing as marked

## Comments

- W-06 applies to TRADES-01, STATS-01, and STATS-02, which have a "previous route" result (rule in ticket 02). Known defects: TRADES-01 and STATS-01 W-06, STATS-02 W-06 and W-14, STATS-03 W-34. STATS-02 now says the toast "Draft is not complete yet", the suggested text of W-14.
- **Maintainer decision, STATS-03 and STATS-04 (decided: keep the stubs):** these two tests answer `GET draft/:id/stats` with a body that the test sets (`fulfillApiRequest`), so the real Draft Stats API response is not exercised. Reasons: the API makes the Draft Stats with the last Pick, so no setup call leaves a Complete Draft without them (STATS-04), and the API measures each duration against the wall clock, so exact words such as "0 seconds" and "1 minute" (W-34) cannot be set (STATS-03). Ticket 02t must test the generation without a stub (added to its checklist). Alternatives that the maintainer did not take: a setup helper that writes `pick_duration` in `helpers/timestamps.ts`, and a way to delete the Draft Stats row.
- The 6C and 6D specs now use the shared `expectRedirectToPreviousRoute` and `expectDraftStatusRedirect` in `e2e/helpers/draft-screen.ts`, because 6E was the third copy (CODING_STANDARDS.md, "E2E code that repeats becomes a helper").
- STATS-03 first failed for a whitespace mismatch, not for W-34. With the known-failure marks turned off, each of the four tests fails on its named defect only.
