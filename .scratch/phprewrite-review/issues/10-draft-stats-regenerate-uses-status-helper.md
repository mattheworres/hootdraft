# 10: Regenerating Draft Stats uses the Draft status helper

**What to build:** The Admin "regenerate Draft Stats" action decides whether a Draft is Complete through the same `DraftService` helper as the rest of the app, not a hard-coded `!== 'complete'` string. Decision on `average_round_seconds`: keep the new formula (total Pick Duration divided by the number of Rounds, which is what "average round" means). Ticket 20 records it as a deliberate change from master, which divided the largest per-Round total by the number of Rounds.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] The Admin Draft Stats controller uses the `DraftService` Complete helper
- [ ] Regenerating Draft Stats still answers 409 for a Draft that isn't Complete and 404 for a missing one
- [ ] A Draft Stats test asserts `average_round_seconds` for a known Draft, so the chosen formula is pinned
- [ ] `AdminTest`, `DraftStatsTest` and `DraftStatsCalculationTest` pass
