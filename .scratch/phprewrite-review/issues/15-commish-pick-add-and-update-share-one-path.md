# 15: Commissioner pick add and update share one path; a saved Pick is never reported as failed

**What to build:** When a Commissioner enters a Pick and it is saved, the client always hears success.

- Today, if loading the last/next Picks throws after the commit, `AddPick` answers "Unable to add pick" for a Pick that is already a Selected Pick
- The Commissioner `add` and `update` actions repeat the same load / fill / validate / call-service shape. Share it
- The route variable is named `{player_id}` although it holds a Pick ("player" is the old code word for a Pick in GLOSSARY.md). Rename the variable to `{pick_id}`; the URL is unchanged

Keep the documented 4.5 behaviour that `UpdatePick` goes through `PickRepository::AddPick`.

**Blocked by:** 12

**Status:** completed

- [x] If anything after the commit fails, the add-pick response still reports success with the saved Pick, and the follow-up data is left out or empty. The failure is logged
- [x] Add and update share one load / fill / validate path in the Commissioner pick controller
- [x] The route variable is `pick_id`, and the URLs and methods are unchanged
- [x] `CommishPickTest` covers a failure after the commit, and still covers the last Pick completing the Draft with `draft_statistics`

## Comments

- `PickService::AddPick` now returns early only when the transaction fails. After the commit, the response is always a success with `pick` and `next_pick`. `last_5_picks` and `next_5_picks` start as `[]`. If loading them fails, the failure is logged and they stay `[]` (or only `next_5_picks` stays `[]`). The draft stats keep their own guard.
- Before this change, the client also got the raw SQL error text for a saved Pick. It does not now.
- `PickController::savePick` is the shared load / fill / validate / save path. `add` and `update` give it the validate and save calls, the load error text and the success status.
- Not fixed here: `PHP8Rewrite.md` (near line 135) still says add-pick does not return `draft_statistics`. It does. This belongs with issue 20.
