# 17: `UtilityService` is renamed for what it does, and repository dependencies are settled

**What to build:** Recipe 4c.3 in PHP8Rewrite.md says a repository takes `\PDO` and other repositories by class name. Two do not:
- `TradeRepository` takes `UtilityService`
- `DraftRepository` takes `DatabaseCacheService` and `UtilityService`

`UtilityService` is one time-formatting method whose name says nothing (possible Mysterious Name). For each dependency, either move it out of the repository (into the calling service) or keep it and record why under Known Compromises. A Draft cache inside the Draft repository may be the right design.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] `UtilityService` has a name that says what it formats, and every caller uses the new name
- [ ] Each of the three repository dependencies is either removed or has a Known Compromises line explaining it
- [ ] JSON output, including formatted times, is unchanged
- [ ] The full suite passes
