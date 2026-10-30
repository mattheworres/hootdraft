# Writing style

Write your findings and explanations meant for human review using ASD-STE100 Simplified Technical English. Be succinct and clear in your writing.

## Commands

Run inside the php container.

- Tests: `docker compose exec -T php composer test` (one file: add `-- tests/Functional/AuthenticationTest.php`)
- PHPStan, glossary terms and tests together: `docker compose exec -T php composer check`

## Agent skills

### Issue tracker

Issues live as local markdown files under `.scratch/<feature>/`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five labels (needs-triage, needs-info, ready-for-agent, ready-for-human, wontfix), recorded as a `Status:` line in each issue file. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.
