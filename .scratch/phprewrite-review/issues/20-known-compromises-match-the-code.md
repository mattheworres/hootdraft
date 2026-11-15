# 20: Known Compromises matches the code

**What to build:** Someone reading the "Known Compromises" section of PHP8Rewrite.md, for example while writing the Playwright suite, gets an accurate list of every way the new API behaves differently from master. Fix the entries that are now wrong, and add what the review found that was never written down. Also record the changes made by tickets 02–18.

Entries that are now wrong:

- 4.1(3) says `/draft/{id}` with a missing Draft "is still a 500". `GET /draft/0` now answers 404
- 4.4(1) says the public Depth Chart PUT "accepts any `position_id`". It now refuses Drafts without Depth Charts and positions from another Draft, and stores 0 as NULL
- 4.5(5) says `TradeAsset` keeps `trade_asset_id`. The entity now declares `tradeasset_id`

Changes never recorded:

- verification key expiry (48h), with the invite key dated 47h ahead (this has been superceded by the 3 scratch tickets for email-verified-split)
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

**Status:** completed

- [x] Every entry above is corrected or added, in the workstream section it belongs to
- [x] Every Known Compromises claim about behaviour was checked against the code, and claims that tickets 02–18 made true or false are updated
- [x] Each entry says whether the AngularJS client depends on it
- [x] No code changes

## Comments

**Agent (implementation):**

- Only `PHP8Rewrite.md` changed. "The old code" means the `develop` branch. Each behaviour entry now ends with a *Client:* note.
- Three items in this ticket did not match the code. The doc follows the code:
  - 4.1(3): only `GET /draft/0` (or an id that is not a number) answers 404. A missing non-zero id is still a 500 (`DraftReadTest::testMissingDraftIsAServerErrorLikeTheOldApp`).
  - "a Draft lock in Draft update, Round Time save": those two have a transaction but no lock. Only Manager create, create-many and delete (`ManagerRepository::LockDraft`), add-pick (`LockCurrentPick`) and Draft Stats lock the draft row.
  - "Round Times saved by Round number": the save did not change. The read (`GetDraftTimers`) now places each stored row by its `draft_round`.
- Verification key expiry (48 hours) and the invite key dated 47 hours ahead are already in the "Email Verified is separate from Enabled" and 4.3 invite entries. Both match the code, so they did not change.
- Stale entries fixed: 4.5 "`draft_statistics` is not returned from add-pick" (struck through, it is returned since 4.6), 4.5(2) `PickHasBeenSelected` (now `Pick::HasBeenSelected()`), and the 4.4(1) depth chart entry.
- Also added from tickets 02–18: integer columns are JSON numbers (03), `/me` `roles` (07), `TransactionService` (11, 12), draft create all-or-nothing (13), depth chart refusal after setup (14), a saved Pick never reported as failed (15), a Pick Duration of 0 counts as selected (16), League rename (09), `DraftStatsRepository` taking `TransactionService` (12), and a "Client changes beyond step 3c" entry.
- Possible old bug, not verified in a browser: a move to the Unassigned depth chart bucket sends `position_id: null`. `$resource` probably drops that segment, so the request probably gets a 404 (`develop` has the same route). Recorded under 4.4.
- Review notes, not changed:
  - Fixed after review (maintainer request): `GLOSSARY.md` "Develop branch" now says the branch is named `develop`, and `master` is its old name.
  - The Standards review found long sentences (over 25 words) in several new entries, and transaction facts repeated across the 4.2, 4.3, 4.5 and 4.6 entries. I kept them so each workstream section stays complete for a reader of one section.
  - "Locked draft" and "Unassigned" (depth chart bucket) are not defined in `GLOSSARY.md`.
- `composer check` passes (316 tests, PHPStan, glossary terms).
