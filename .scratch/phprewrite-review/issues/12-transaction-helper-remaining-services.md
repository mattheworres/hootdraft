# 12: Pick, Trade, Pro Player, Draft Stats and user saves use the transaction helper

**What to build:** The remaining transactional saves get the same guarantee as ticket 11: a failure of any kind rolls back, and the Draft cache is flushed either way. Move `PickService`, `TradeService`, `ProPlayerService`, `DraftStatsRepository`, `LoginUserService` and `UsersInvitationService` onto the helper, and delete their copied transaction code.

**Blocked by:** 11

**Status:** ready-for-agent

- [ ] The six classes above use the helper, and no other `beginTransaction` / `rollBack` code is left outside it
- [ ] `DraftStatsRepository` still joins an open transaction and keeps its `FOR UPDATE` lock
- [ ] Adding a Pick still holds the double-submit guard (`LockCurrentPick`) inside the transaction
- [ ] Registration and invite still send their email inside the transaction
- [ ] Response shapes and messages are unchanged, and the full suite passes
