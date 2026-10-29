# 16: `TradeService` builds the trade assets

**What to build:** Entering a Trade behaves exactly as it does now, but `TradeService` owns turning the request into a `Trade` and its assets. Today the Commissioner trade controller reaches into the fields of `Trade`, `Pick` and `Manager` to build each `TradeAsset` (possible Feature Envy). While moving it, rename `$assets_json`, which holds an array, not JSON.

**Blocked by:** 12

**Status:** ready-for-agent

- [ ] The controller hands the request data to `TradeService`, which builds and validates the Trade
- [ ] The response shape, the validation messages and the 400 for a missing `trade_assets` list are unchanged
- [ ] No variable named `*_json` holds anything other than a JSON string
- [ ] `TradeTest`, `TradeServiceTest` and `TradeValidatorTest` pass. If `TradeServiceTest` now covers the asset building, add a unit test for it
