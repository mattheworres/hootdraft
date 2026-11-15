# 03: Flip clock prototype

**What to build:** A throwaway prototype and a report, so that the maintainer can choose the flip clock for the new client. See `AngularRewrite.md`, Section 3, phase 3.

**Blocked by:** none

**Status:** ready-for-agent

**Facts (2026-11-14):** The app uses FlipClock 0.7.7 with `angular-flipclock` in one place, `app/features/draft/board.component.html` (`MinuteCounter`, countdown, a stop callback). FlipClock 1.0.1 was published in August 2025 as a rewrite in TypeScript without jQuery. Its dependencies are `solid-js`, `goober`, and `date-fns`.

- [ ] A zoneless Angular page, outside `client/`, with FlipClock 1.0.1 behind one small wrapper component: set the seconds, start, stop, and a "time is up" output
- [ ] Report, appearance: screenshots beside the current clock
- [ ] Report, zoneless: the clock runs and the "time is up" output updates a signal with no zone.js
- [ ] Report, Content Security Policy: behavior with a policy that has no `unsafe-inline` for styles (`goober` injects CSS at run time). If it fails, state what the policy must permit
- [ ] Report, size: bytes added to the production bundle, compressed
- [ ] Report, dependencies: for `solid-js`, `goober`, and `date-fns`, the size, maintainers, release history, and known vulnerabilities
- [ ] A recommendation, with the reasons
- [ ] **Stop.** The maintainer chooses: FlipClock 1.0.1, or a framework-agnostic custom element in its own repository. Record the decision in `AngularRewrite.md` and below
- [ ] If the choice is a custom element: add tickets for its discrete steps, blocked by this ticket, and make ticket 06 blocked by them
- [ ] Delete the prototype
