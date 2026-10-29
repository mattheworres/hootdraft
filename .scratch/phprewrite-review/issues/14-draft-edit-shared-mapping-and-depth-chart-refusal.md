# 14: Draft edit shares one payload mapping and refuses Depth Chart changes on a Draft that isn't Setting Up

**What to build:** A Commissioner editing a Draft that is In Progress can't be misled about its Depth Chart settings. Today the server silently ignores `using_depth_charts` and the positions unless the Draft is Setting Up, still answers 200, and the client then updates its in-memory Draft with values that were never stored. Make the change visible: answer 400 with a validation message when those fields differ from what is stored, and let the client keep the stored values.

While you are in this code:
- Create and update repeat the same payload-to-`Draft` mapping and the Depth Chart validation block. Share them
- On update, the loaded `using_depth_charts` can be bound as a PHP `false` (an empty string under strict SQL). Always store 0 or 1
- Replace the hard-coded `"undrafted"` status string with the `DraftService` Setting Up helper

**Blocked by:** 13 (same controller and service)

**Status:** ready-for-agent

- [ ] Changing `using_depth_charts` or the positions on a Draft that isn't Setting Up answers 400 with a clear message and changes nothing. Sending the stored values unchanged still succeeds
- [ ] The Draft edit screen shows the 400 message and leaves the Draft as stored
- [ ] Create and update share one mapping, and one Depth Chart validation path
- [ ] `using_depth_charts` is always bound as 0 or 1
- [ ] Functional tests cover: editing an In Progress Draft with Depth Charts off (the PHP `false` case), the refused change, and turning Depth Charts off while Setting Up (the positions are deleted)
