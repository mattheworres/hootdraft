# 04: Angular skeleton

**What to build:** The new Angular project in `client/` with everything that the feature phases share. See `AngularRewrite.md`, Section 2 and Section 3, phase 4, and `docs/adr/0004-angular-client-architecture.md`.

**Blocked by:** 02

**Status:** ready-for-agent

- [ ] Confirm the latest stable Angular and TypeScript on angular.dev, then `ng new` in `client/`: standalone, zoneless, strict, Less, Vitest. Delete `app-new/`
- [ ] Only the permitted packages: Angular, Angular CDK, NgRx SignalStore, RxJS. Ask the maintainer before any other package
- [ ] The existing Less files and Bootstrap 3.4.1 CSS and Font Awesome 4.7 load unchanged. No jQuery and no Bootstrap JavaScript
- [ ] Routes for all 26 URLs, lazy-loaded, with placeholder components, and the guards for Commissioner and Admin routes
- [ ] API layer: typed functions for the API routes, types that mirror the API fields exactly, and the mapping to client types that use `GLOSSARY.md` terms. Send the `X-Requested-With` header and the Draft Password header
- [ ] Login session store (SignalStore): `/me` on start, login, logout, and the 401 handling that routes to `/login`
- [ ] Shared widgets that output the Bootstrap 3 markup: navigation bar, modal, confirm prompt, toast messages, loading indicator, tooltip, dropdown
- [ ] Unit tests (Vitest, test-first) for the session store and the mapping
- [ ] The Docker box and the E2E stack serve the new client beside the AngularJS client
- [ ] CI runs `ng lint`, `ng test` and `ng build`, and runs the migrated inventory rows against the new client
- [ ] No component subscribes to an Observable
