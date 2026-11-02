# 09: The Pro Player CSV upload accepts the bundled MLB file

**What to build:** An Admin can upload every bundled Pro Player CSV in `resources/`. Today a row whose name column isn't exactly "Last, First" makes the upload refuse the whole file. The bundled MLB file has one such row (`Henry;RP;SDG`), so that file can no longer be imported. The develop branch inserted these rows as "Unknown Player". Decision: import a single-name row with the whole name as the last name and an empty first name, so `Henry` stays findable as Henry.

Also rename the variable that carries the league but is called `$sport`. Review found more places that use "sport" for a League, so this issue also does the Sport → League rename for the Pro Player admin screen and the internal PHP names. Wire names that Draft clients use stay: the `draft_sport` field, the `sports` field on a Draft, the `sport` field on draft create/update, and the "Draft sport is an invalid value." error.

**Blocked by:** 01

**Status:** completed

- [x] A row with no comma in the name column is imported with that name as `last_name` and an empty `first_name`
- [x] Rows that genuinely can't be parsed are still refused, with an error naming the line
- [x] Every CSV in `resources/` uploads successfully through `POST /admin/proplayers`
- [x] The existing behaviour stays: atomic delete-and-insert, blank lines skipped, BOM ignored, the empty-file refusal
- [x] Pro Player search finds the single-name player
- [x] Functional tests cover a single-name row, plus the MLB file (or a trimmed copy of it)
- [x] Breaking change, agreed: `GET /admin/sports` becomes `GET /admin/leagues` (route name `admin_leagues_get`), and the `POST /admin/proplayers` form field `sport` becomes `league`. The bundled front end, `docs/route-inventory.md` and `PHP8Rewrite.md` use the new names
- [x] The Pro Player admin screen says League: label, placeholder and the error "League QVC is an invalid value."
- [x] Internal PHP and JS names say League: `IsUploadLeagueValid`, `IsDraftLeagueValid`, `DraftDataRepository::GetLeagues`, `getLeagues` in the model API, and the variables and test names that held a League
- [x] GLOSSARY.md has a **League** entry, and the **Draft** entry points to it
- [x] The refused-row error says "have a blank name or a comma with nothing on one side", because one-name rows are now valid and "Last, First" no longer describes the rule
- [x] The `<label>` for the CSV file input points to the input (`for="csvFile"`)

**Out of scope:** `label-type="sport"` stays, because it sets the shared `.draft-sport` CSS class. The Draft edit screen keeps its `sport` names, because they follow `draft_sport`
