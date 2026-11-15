# 12: Pick, Trade, Pro Player, Draft Stats and user saves use the transaction helper

**What to build:** The remaining transactional saves get the same guarantee as ticket 11: a failure of any kind rolls back, and the Draft cache is flushed either way. Move `PickService`, `TradeService`, `ProPlayerService`, `DraftStatsRepository`, `LoginUserService` and `UsersInvitationService` onto the helper, and delete their copied transaction code.

**Blocked by:** 11

**Status:** completed

- [x] The six classes above use the helper, and no other `beginTransaction` / `rollBack` code is left outside it
- [x] `DraftStatsRepository` still joins an open transaction and keeps its `FOR UPDATE` lock
- [x] Adding a Pick still holds the double-submit guard (`LockCurrentPick`) inside the transaction
- [x] Registration and invite still send their email inside the transaction
- [x] Response shapes and messages are unchanged, and the full suite passes

## Comments

- From ticket 11: `DraftService::DeleteDraft` now joins an open transaction through `TransactionService::Run`. If an `\Error` occurs inside a joined Draft delete, it goes out of `LoginUserService::DeleteUser`. That method catches only `\Exception`, so today the transaction stays open. Moving `DeleteUser` onto the helper fixes this.
- Done. `DraftStatsRepository` keeps `DraftRepository` (it calls `UpdateStatsTimestamp`) and also takes `TransactionService`. A repository now depends on a service, but there is no dependency cycle. `PickService::UpdatePick` had a `rollBack()` call but opens no transaction. That flush did nothing, so it is removed. An `\Error` now rolls back and is rethrown in every method that changed. New tests: `PickServiceTest`, `LoginUserServiceTest`, a `TradeServiceTest` `\Error` case, `CommishTest::testAFailedInvitationEmailLeavesNoAccount` and `DraftStatsCalculationTest::testCalculatingInsideAnOpenTransactionJoinsIt`.
