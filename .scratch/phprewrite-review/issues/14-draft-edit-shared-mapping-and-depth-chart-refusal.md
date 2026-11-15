# 14: Draft edit shares one payload mapping and refuses Depth Chart changes on a Draft that isn't Setting Up

**What to build:** A Commissioner editing a Draft that is In Progress can't be misled about its Depth Chart settings. Today the server silently ignores `using_depth_charts` and the positions unless the Draft is Setting Up, still answers 200, and the client then updates its in-memory Draft with values that were never stored. Make the change visible: answer 400 with a validation message when those fields differ from what is stored, and let the client keep the stored values.

While you are in this code:

- Create and update repeat the same payload-to-`Draft` mapping and the Depth Chart validation block. Share them
- On update, the loaded `using_depth_charts` can be bound as a PHP `false` (an empty string under strict SQL). Always store 0 or 1
- Replace the hard-coded `"undrafted"` status string with the `DraftService` Setting Up helper

**Blocked by:** 13 (same controller and service)

**Status:** completed

- [x] Changing `using_depth_charts` or the positions on a Draft that isn't Setting Up answers 400 with a clear message and changes nothing. Sending the stored values unchanged still succeeds
- [x] The Draft edit screen shows the 400 message and leaves the Draft as stored
- [x] Create and update share one mapping, and one Depth Chart validation path
- [x] `using_depth_charts` is always bound as 0 or 1
- [x] Functional tests cover: editing an In Progress Draft with Depth Charts off (the PHP `false` case), the refused change, and turning Depth Charts off while Setting Up (the positions are deleted)
- [x] Magic strings for draft statuses is placed on the `Draft` entity
- [x] Client fix: `errorService` shows the server's 400 messages for direct `$resource` callers (Draft edit, Draft create, trades, picks, timers), not "Whoops… (undefined)"
- [x] The Draft and Depth Chart validation moves from the controller into `DraftValidator`, and one `Draft` accessor replaces the scattered `using_depth_charts == 1` checks

## Comments

- The server compares the positions only when Depth Charts are on. When they are off, the edit screen fills in default positions for the sport (`sportChanged` → `createPositionsBySport`), so the sent positions are not the stored ones.
- The comparison uses `position` and `slots` in `display_order`. A different order is a change, so the server refuses it.
- Known edge case, not fixed: an In Progress Draft with Depth Charts on but no stored positions. The client fills in defaults, so the server refuses each edit. Create and update do not let this state occur. Only old data can have it.
- `errorService.parseValidationErrorsFromResponse` did not show the server's messages for direct `$resource` callers (Draft edit, Draft create, trades, picks, timers). It read the `{data, status}` wrapper that only `authenticationService` adds, and showed "Whoops… (undefined)". It now accepts both shapes. The built `js/` bundle is not in git: run the gulp build to see the change.
- `DraftValidator::IsDraftValidForCreate` and `IsDraftValidForUpdate` own the Draft and Depth Chart validation, including the refusal. The controller only maps the payload.
- Read `using_depth_charts` with `Draft::UsesDepthCharts()`. The raw property can be 0/1 (column and payload), a bool (`Load()`) or null (a locked Draft). Only the bind, `Load()` and the locked-Draft code use it directly.
- `DraftService::STATUS_SETTING_UP` holds the stored `'undrafted'` value. Use it instead of the literal.
