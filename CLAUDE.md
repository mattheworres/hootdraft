# Writing style

Follow `docs/writing-style.md`, but stick to these rules for agentic conversation as well (in particular, maintain signal integrity but keep sentence complexity and verbosity to a minimum).

## Commands

Run inside the php container.

- Tests: `docker compose exec -T php composer test` (one file: add `-- tests/Functional/AuthenticationTest.php`)
- PHPStan, glossary terms and tests together: `docker compose exec -T php composer check`
- E2E tests: `npx playwright test --project=chromium --project=helpers` in `e2e/`. CI runs the other three browsers.
- Hand-off: the whole E2E suite (above, no `-g` or file filter) runs after your last change to `e2e/` or client code, including fixes from code review and CodeScene. Any later edit means another run. `bin/check-e2e-fresh` verifies this; a Stop hook runs it.
- Edit files with the Edit tool, not scripted replaces.
- Commits will be blocked.

## Comments

Comment only non-obvious decisions: workarounds, external constraints, and reasons the code can't express. Code should be self-documenting otherwise.

## Agent skills

### Issue tracker

Issues live as local markdown files under `.scratch/<feature>/`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five labels (needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix), plus `awaiting-human-review` for finished work the maintainer has not reviewed yet. Recorded as a `Status:` line in each issue file. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.

### Code Health

In the `mattpocock-skills:implement` skill, run the CodeScene `code_health_review` tool before the `code-review` skill. Plan the refactors from its Code Health findings together with the review findings.

In the `mattpocock-skills:code-review` skill, number every actionable finding with one scheme, 1–N, across both axes. Keep the Standards and Spec sections separate. Use bullets only for notes that need no action.

## CodeScene MCP

After changing code, run `code_health_review` on the changed files. If Code Health drops, fix the issues, run the review again, and use `code_health_score` to confirm the result. Before finishing, run `pre_commit_code_health_safeguard` and only stop when it passes.
