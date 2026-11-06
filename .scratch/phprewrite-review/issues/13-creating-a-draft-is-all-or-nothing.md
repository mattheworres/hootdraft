# 13: Creating a Draft is all-or-nothing

**What to build:** A Commissioner whose new Draft fails to save is left with no Draft at all, and can fix the form and try again. Today `CreateNewDraft` is the only Draft save without a transaction. A Depth Chart position name longer than the column allows (`depth_chart_positions.position` is 6 characters; the client input has no `maxlength`) makes the position save throw after the Draft row already exists. The Commissioner sees a raw SQL error, and the retry then fails with "Draft name is already taken".

**Blocked by:** 11

**Status:** completed

- [x] Creating a Draft (the Draft row and Depth Chart positions) runs in one transaction through the helper. Creating a Draft writes no Round Times: the Commissioner sets them later, on the Draft that exists
- [x] A position name that is too long is refused by `DepthChartPositionValidator` with a validation message, before anything is written. The client input gets a matching `maxlength`
- [x] A failure part-way leaves no Draft or position rows, and returns a 400 without SQL in the message
- [x] Functional tests cover: a too-long position is refused with nothing saved, and a forced failure after the Draft row and its positions are written leaves nothing behind and a retry with the same name succeeds
