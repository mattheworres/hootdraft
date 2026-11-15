# 01: Functional tests share one set of helpers

**What to build:** Every functional test builds its Commissioner, Draft and session through one shared base class, so the regression tests added by later tickets reuse the same helpers instead of adding a fifth copy. Today `CommishTest`, `DraftReadTest`, `ManagerTest` and `PickTest` each redefine `db()`, `createUser()`, `createDraft()` and `logIn()`, although `CommishDraftTestCase` already has them. This is a pure test refactor with no change to production code.

**Blocked by:** None (can start immediately).

**Status:** completed

- [x] `db()`, `createUser()`, `createDraft()` and `logIn()` are defined once, in the shared functional test base, and the four tests above use it
- [x] Where the copies differ (default arguments, which columns get filled in), the shared version covers every caller without changing what any test asserts
- [x] No production code changes
- [x] The full PHPUnit suite passes in the Docker box with the same number of tests as before
