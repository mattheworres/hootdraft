# 08: A fresh tab with a valid session shows the logged-in state

**What to build:** Opening HootDraft in a new tab while the session cookie is still valid shows the user as logged in straight away, without a reload. Today the client calls `/me` asynchronously at startup, but the draft table and login screens check "is authenticated" once when they load. A fresh tab (empty `$sessionStorage`) therefore shows the logged-out state until the user reloads.

**Blocked by:** None (can start immediately).

**Status:** ready-for-agent

- [ ] The authentication service exposes the startup `/me` check as something components can wait for (a promise, or a route resolve)
- [ ] The home draft table, the login screen and the navigation reflect the session once `/me` answers, with no reload
- [ ] An anonymous visitor still sees public pages without being sent to `/login`
- [ ] The 401 interceptor still only acts when the tab believed it was logged in
- [ ] `gulp` lint and build pass. Check by hand: log in, open a new tab, and the draft list shows the Commissioner's Drafts
