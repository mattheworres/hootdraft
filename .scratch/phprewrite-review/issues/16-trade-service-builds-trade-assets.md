# 16: `TradeService` builds the trade assets

**What to build:** Entering a Trade behaves exactly as it does now, but `TradeService` owns turning the request into a `Trade` and its assets. Today the Commissioner trade controller reaches into the fields of `Trade`, `Pick` and `Manager` to build each `TradeAsset` (possible Feature Envy). While moving it, rename `$assets_json`, which holds an array, not JSON.

**Blocked by:** 12

**Status:** completed

- [x] The controller hands the request data to `TradeService`, which builds and validates the Trade
- [x] The response shape, the validation messages and the 400 for a missing `trade_assets` list are unchanged
- [x] No variable named `*_json` holds anything other than a JSON string
- [x] `TradeTest`, `TradeServiceTest` and `TradeValidatorTest` pass. If `TradeServiceTest` now covers the asset building, add a unit test for it
- [x] `_BuildAsset` finds the old and new manager one time, then sets the asset fields one time (no mirrored branches)
- [x] "Has this pick been selected" moves from `PickService::PickHasBeenSelected` to `Pick::HasBeenSelected()`, and the Commissioner trade controller no longer depends on `PickService`. A unit test covers the method
- [x] `BuildTrade` returns a small typed result (the Trade, or the errors) in place of a `HootDraftResponse` with a dynamic `trade` property. The JSON the client gets is unchanged
- [x] `PickRepository` and `PickSearchRepository` set `selected` with `Pick::HasBeenSelected()`, so there is one rule. A test covers every read that sets it (Matt added this)

## Comments

- `TradeService::BuildTrade(Draft, manager1_id, manager2_id, assetIds)` builds the Trade and its assets, then validates it with `TradeValidator`. It returns a `TradeBuildResult` (see below).
- The controller reads the request values and gives them to `BuildTrade`. If the build fails, it sends `HootDraftResponse(false, errors)` with a 400. This is the same JSON as before. Then it calls `EnterTrade`. The controller does not use `PickRepository` and `PickService` now.
- The per-asset logic is in the private `TradeService::_BuildAsset`. `$assets_json` is now `$assetIds`. No `*_json` variable is left in `src/` or `tests/`.
- `TradeService` now also gets `ManagerRepository` and `TradeValidator` (6 dependencies).
- `TradeServiceTest` has 5 new `BuildTrade` tests: assets move to the other manager (with `was_drafted`), a missing asset list, an asset of neither manager, a manager that cannot load, and validator errors. A `service()` helper builds the service with stubs by default.
- `composer check` passes (311 tests).
- Review follow-ups (the three items added after the first review):
  - `_BuildAsset` uses one `match` to find the old and new side (id and Manager), then sets the four fields one time. The ids are still the request values, so their types do not change.
  - `Pick::HasBeenSelected(): bool` replaces `PickService::PickHasBeenSelected`, which had no other callers. `TradeService` stores it as `1`/`0`. `PickTest` covers it. `TradeServiceTest` now sets `pick_time` and `pick_duration` on the pick and does not stub a return value.
  - `HootDraft\Domain\Models\TradeBuildResult` has a nullable `trade` and `errors`. You can create it only through `Built(Trade)` or `Refused(errors)`, so it never holds both. The controller checks `$built->trade === null`, which PHPStan can see.
  - `TradeTest` now compares the full failure bodies (`{success: false, errors: [...]}`) and checks `was_drafted` in the response. That value is `'1'`/`'0'` (strings). This did not change: `TradeRepository::SaveAssets` binds `was_drafted` by reference, and PDO turns it into a string.
- `PickRepository` now sets `selected` with `Pick::HasBeenSelected()` in all 8 places (`Load`, `LoadUpdatedPicks`, `GetCurrentPick`, `GetPreviousPick`, `LoadLastPicks`, `LoadNextPicks`, `LoadManagerPicks`, `LoadRoundPicks`). The old rule was `pick_duration > 0`. The new rule is that `pick_duration` is not null.
  - Behaviour change: `PickService` sets `pick_duration` to the seconds since the previous pick. A Pick selected in the same second as the Pick before it gets `0`. Before, the board and search showed that Pick as not selected. Now they show it as selected.
  - `PickTest` (functional) `testAZeroPickDurationCountsAsSelectedOnEveryRead` sets the Pick Duration of a Selected Pick to `0` and checks `selected` through one route for each of the 8 reads and for `Search`. `CommishPickTest::testAlreadyDrafted` checks it for `SearchAlreadyDrafted`.
- `PickSearchRepository` (`Search` and `SearchAlreadyDrafted`) used the old rule (`pick_duration > 0`). It now uses `Pick::HasBeenSelected()` too, so the board and search agree. (Second review.)
- Correction: at `HEAD`, the Commissioner trade controller used `PickService`, not `TradeService`. The checklist item now says this.
- Glossary: `GLOSSARY.md` now defines **Trade Asset**. The test names, case labels and these notes use "Selected Pick" and "Pick Duration" in place of "made pick" and "picked".
