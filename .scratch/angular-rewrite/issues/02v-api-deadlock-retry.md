# 02v: API: retry a transaction after a deadlock

**What to build:** A fix in the API for a defect that ticket 02a found. When two Drafts move to In Progress at the same time, the API can refuse one with `SQLSTATE[40001]: Serialization failure: 1213 Deadlock found`. See `AngularRewrite.md`, Section 2.1, item 11, and "Known compromises".

**Blocked by:** 02a

**Status:** ready-for-agent

## Cause

`DraftService::UpdateDraftStatus` runs one transaction: it deletes the Picks and Trades of the Draft, then inserts the new Picks. For a new Draft the deletes match no rows, but InnoDB still takes a gap lock at the end of the `draft_id` index. Two such transactions each hold the gap lock and each wait to insert into it, so the database ends one of them. This is a reading of the code. Confirm it with `SHOW ENGINE INNODB STATUS` after a deadlock.

## Work

- [ ] A functional test that shows the deadlock: two connections move two new Drafts to In Progress at the same time
- [ ] `TransactionService::Run` runs the work again after a deadlock (SQLSTATE `40001`), up to three times, with a rollback before each new attempt. A transaction that is already open is not retried by the inner call
- [ ] Check that the work of each `TransactionService::Run` caller is safe to run twice. Record a caller that is not safe, and do not retry it
- [ ] Optional: skip the deletes in `UpdateDraftStatus` when the Draft has no Picks and no Trades, so this transaction takes no gap lock
- [ ] Remove the deadlock retry from `send()` in `e2e/helpers/api.ts`, so the E2E suite proves the fix
- [ ] Remove the deadlock lines from "Known compromises" in `AngularRewrite.md` and from "Known limits" in `e2e/README.md`
- [ ] `composer check` passes, and the E2E suite passes with `--repeat-each=4` in Chromium
