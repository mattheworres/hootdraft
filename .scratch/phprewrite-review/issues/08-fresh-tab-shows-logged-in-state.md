# 08: A fresh tab with a valid session shows the logged-in state

**What to build:** Opening HootDraft in a new tab while the session cookie is still valid shows the user as logged in straight away, without a reload. Today the client calls `/me` asynchronously at startup, but the draft table and login screens check "is authenticated" once when they load. A fresh tab (empty `$sessionStorage`) therefore shows the logged-out state until the user reloads.

**Blocked by:** None (can start immediately).

**Status:** complete

- [x] The authentication service exposes the startup `/me` check as something components can wait for (a promise, or a route resolve)
- [x] The home draft table, the login screen and the navigation reflect the session once `/me` answers, with no reload
- [x] An anonymous visitor still sees public pages without being sent to `/login`
- [x] The 401 interceptor still only acts when the tab believed it was logged in
- [ ] `gulp` lint and build pass. Check by hand: log in, open a new tab, and the draft list shows the Commissioner's Drafts

## Comments

- `authenticationService.verifySession()` now keeps a promise. `sessionChecked()` returns it. It resolves when `/me` answers, with any status, and never rejects.
- The `sessionCheckedResolve` constant (`app/features/shared/constants/`) is a route resolve. The home routes and the logged-out-only screens (login, lost password, reset password, register, verify, invite setup) use it. Those screens load only after `/me` answers, so their one-time `isAuthenticated()` check is correct.
- The navigation calls `isAuthenticated()` in its template on each digest, so it needed no change.
- The 401 interceptor did not change. `handleSessionExpired()` still does nothing when the tab is not logged in.
- `gulp build` (with `js-lint`) passes. The check by hand in a new tab is for the maintainer to do.
- Review notes, not changed: (1) The home page now asks for the draft list only after `/me` answers, so the first load takes one more round trip. (2) If `/me` hangs, the waiting routes show an empty view until the browser stops the request. There is no timeout. (3) Routes without the resolve (`/draft/*`, `/commish/*`, `/admin/*`, `/profile`) have no one-time `isAuthenticated()` check today. If you add one, also add `resolve: sessionCheckedResolve` to that route.
