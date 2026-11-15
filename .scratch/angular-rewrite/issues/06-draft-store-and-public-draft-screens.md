# 06: Draft store and public Draft screens

**What to build:** The draft store and every screen that a viewer without a login can open. This is the highest-risk phase. See `AngularRewrite.md`, Section 3, phase 6, and Section 2.1, item 6.

**Blocked by:** 03, 05

**Status:** ready-for-agent

**Note:** Build the draft store first, as its own pull request, then one pull request for each route group.

- [ ] Draft store (SignalStore) that replaces `app/features/shared/services/draft.service.js`: load a Draft, hold its status, and expose signals to the screens
- [ ] A "draft updates" source interface with one implementation, polling. Polling runs only while the Draft is In Progress and is not locked by a Draft Password. The store does not know that the source is polling
- [ ] Unit tests (test-first) for the store: start and stop of polling, a Draft change, a status change to Complete, the Draft Password lock
- [ ] Draft index for Setting Up, In Progress, and Complete
- [ ] Draft Board with the flip clock behind one wrapper component (the choice from ticket 03) and the "time is up" state
- [ ] Picks by Round, Picks by Manager, search, Draft Stats, Trades, Depth Chart (with drag and drop through the Angular CDK)
- [ ] The Draft Password prompt
- [ ] The multi-viewer inventory rows pass
- [ ] The inventory rows of each route group pass on the new client in four browsers and are marked as migrated
- [ ] Each pull request has the screenshot report
