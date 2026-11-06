# 11: One transaction helper; Draft, Manager and Round Time saves roll back on any error

**What to build:** Saves that use a transaction always roll back on failure, including PHP `\Error`s such as a `TypeError`. Today they all `catch (\Exception)`, so an `\Error` skips the rollback and the client gets a 500. The begin / commit / rollback / "join an open transaction" code is copied into nine classes, with three private `rollBack()` copies. This ticket introduces one helper (run this callable in a transaction, join one that is already open, roll back on any `\Throwable`, and flush pending Draft cache resets on commit and on rollback). It then moves the Draft, Manager and Round Time services onto it. Ticket 12 moves the rest.

**Blocked by:** 01

**Status:** completed

- [x] One transaction helper exists. Joining an open transaction behaves as `DraftService::DeleteDraft` does today, so `LoginUserService::DeleteUser` still wraps every Draft delete
- [x] `DraftService`, `ManagerService` and `RoundTimeService` use it, and their private rollback code is gone
- [x] Response shapes and messages for failures are unchanged
- [x] A test proves that an `\Error` thrown inside a transaction rolls back and leaves the connection outside a transaction
- [x] The full suite passes, including `AdminTest::testDeleteUserIsAllOrNothing`
- [x] Additional: reduce duplicated code in test fakes - `pdo(int $expectedCommits, int $expectedRollbacks)` - move it into a shared test trait. DraftServiceTest has two delete tests that check `never()` on `rollback`, and this is OK.
