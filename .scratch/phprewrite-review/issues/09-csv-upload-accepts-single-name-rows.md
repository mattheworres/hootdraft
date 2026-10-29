# 09: The Pro Player CSV upload accepts the bundled MLB file

**What to build:** An Admin can upload every bundled Pro Player CSV in `resources/`. Today a row whose name column isn't exactly "Last, First" makes the upload refuse the whole file. The bundled MLB file has one such row (`Henry;RP;SDG`), so that file can no longer be imported. Master inserted these rows as "Unknown Player". Decision: import a single-name row with the whole name as the last name and an empty first name, so `Henry` stays findable as Henry.

Also rename the variable that carries the league but is called `$sport`.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] A row with no comma in the name column is imported with that name as `last_name` and an empty `first_name`
- [ ] Rows that genuinely can't be parsed are still refused, with an error naming the line
- [ ] Every CSV in `resources/` uploads successfully through `POST /admin/proplayers`
- [ ] The existing behaviour stays: atomic delete-and-insert, blank lines skipped, BOM ignored, the empty-file refusal
- [ ] Pro Player search finds the single-name player
- [ ] Functional tests cover a single-name row, plus the MLB file (or a trimmed copy of it)
