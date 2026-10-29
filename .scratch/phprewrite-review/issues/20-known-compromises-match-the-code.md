# 20: Known Compromises matches the code

**What to build:** Someone reading the "Known Compromises" section of PHP8Rewrite.md, for example while writing the Playwright suite, gets an accurate list of every way the new API behaves differently from master. Fix the entries that are now wrong, and add what the review found that was never written down. Also record the changes made by tickets 02–18.

Entries that are now wrong:
- 4.1(3) says `/draft/{id}` with a missing Draft "is still a 500". `GET /draft/0` now answers 404
- 4.4(1) says the public Depth Chart PUT "accepts any `position_id`". It now refuses Drafts without Depth Charts and positions from another Draft, and stores 0 as NULL
- 4.5(5) says `TradeAsset` keeps `trade_asset_id`. The entity now declares `tradeasset_id`

Changes never recorded:
- verification key expiry (48h), with the invite key dated 47h ahead
- the 400 for a malformed JSON body
- transactions and a Draft lock in Draft update, Round Time save and every Manager save
- Draft and Manager name checks moved from `LIKE` to equality
- new validation:
  - reorder rejects duplicate ids
  - create-many compares names case-insensitively and refuses an empty list
  - blank position names or negative slots are refused
  - a Trade needs at least one asset and two different Managers
  - updating a Pick requires `pick_time`
  - an Admin can't remove their own Admin role or disable themselves
  - "Name is missing."
- Round Times saved by Round number, not list position
- a locked Draft gets no lookup data, and `historical_teams` is nulled
- the timer lookup failing now fails the whole `LoadUpdatedData` poll
- `picks/last` and `picks/next` cap `amount` at 100
- the `LockCurrentPick` double-submit guard
- CSV parsing splits on the first comma, converts Windows-1252 and skips the header only on the first row
- the `draft.service.js` `pendingDefers` rewrite and the captcha reload (beyond step 3c's "smallest changes")
- the `average_round_seconds` formula (see ticket 10)

**Blocked by:** 03, 04, 05, 06, 07, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18

**Status:** ready-for-agent

- [ ] Every entry above is corrected or added, in the workstream section it belongs to
- [ ] Every Known Compromises claim about behaviour was checked against the code, and claims that tickets 02–18 made true or false are updated
- [ ] Each entry says whether the AngularJS client depends on it
- [ ] No code changes
