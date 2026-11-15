# Angular rewrite

The spec is `AngularRewrite.md` at the repository root. It holds the decisions, the phases, and the known compromises. Do not copy its content here.

Architecture decisions: `docs/adr/0004-angular-client-architecture.md`, `docs/adr/0005-no-server-side-rendering.md`.

## Tickets

One ticket for each phase. A phase that is too large for one session is split into route-group tickets (`05a-...`, `05b-...`) when the phase starts. Each route group is one pull request.

1. `01-flow-inventory`
2. `02-e2e-stack-ci-and-suite-on-angularjs` (blocked by 01)
3. `03-flip-clock-prototype` (blocked by none; maintainer decision at the end)
4. `04-angular-skeleton` (blocked by 02)
5. `05-authentication-and-home` (blocked by 04)
6. `06-draft-store-and-public-draft-screens` (blocked by 03, 05)
7. `07-commissioner-screens` (blocked by 06)
8. `08-admin-screens` (blocked by 05)
9. `09-cutover` (blocked by 06, 07, 08)
10. `10-relaunch` (blocked by 09)
