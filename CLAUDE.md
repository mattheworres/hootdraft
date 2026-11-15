# Writing style

Follow `docs/writing-style.md`, but stick to these rules for agentic conversation as well (in particular, maintain signal integrity but keep sentence complexity and verbosity to a minimum).

## Commands

Run inside the php container.

- Tests: `docker compose exec -T php composer test` (one file: add `-- tests/Functional/AuthenticationTest.php`)
- PHPStan, glossary terms and tests together: `docker compose exec -T php composer check`
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
