# 17: `UtilityService` is renamed for what it does, and repository dependencies are settled

**What to build:** Recipe 4c.3 in PHP8Rewrite.md says a repository takes `\PDO` and other repositories by class name. Two do not:

- `TradeRepository` takes `UtilityService`
- `DraftRepository` takes `DatabaseCacheService` and `UtilityService`

`UtilityService` is one time-formatting method whose name says nothing (possible Mysterious Name). For each dependency, either move it out of the repository (into the calling service) or keep it and record why under Known Compromises. A Draft cache inside the Draft repository may be the right design.

**Blocked by:** 01

**Status:** approved

- [x] `UtilityService` has been renamed `ClientTimeFormatter`, and every caller uses the new name
- [x] Each of the three repository dependencies is either removed or has a Known Compromises line explaining it
- [x] JSON output, including formatted times, is unchanged
- [x] The full suite passes
