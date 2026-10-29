# 03: The AngularJS client handles numbers from the API

**What to build:** The client behaves correctly now that the API returns integer columns as JSON numbers. On PHP 8.1+, pdo_mysql returns native ints, where master returned strings. Decision: fix the client comparisons, not the server, so the planned Angular migration doesn't inherit string-typed flags. No server change.

These behaviours broke:

- The Admin edit-user modal shows an enabled user as disabled (it compares `enabled` with `'1'`), and saving it disables the account
- The first-pick help text on Commissioner pick entry never shows (it compares `player_pick` with `'1'`)
- The pick timers editor never recognises a static Round Time (it compares `is_static_time` with `'1'`)
- The Draft is never reused from the client cache, and Draft-change detection always fires: `draft_id` (now a number) is compared strictly with the route parameter (a string). This happens in the draft service in several places and in the Draft index component

**Blocked by:** None (can start immediately).

**Status:** completed

- [x] Every comparison of an API value with a numeric string literal (`=== '1'`, `== '1'` and similar) is replaced with one that works whether the value arrives as a number or a string. Search the client for all of them, not just the ones listed above
- [x] Every comparison of an API id with a `$routeParams` id converts both sides to the same type
- [x] The Admin edit-user modal shows the right enabled state, and saving an enabled user keeps them enabled
- [x] The first-pick help text shows on the first Pick of a Draft
- [x] A Draft with a static Round Time opens the timers editor in static mode
- [x] Moving between screens of one Draft no longer re-fetches the Draft each time (visible in the network tab)
- [x] `gulp` lint and build pass
