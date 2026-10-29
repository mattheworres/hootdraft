# 04: Pick search returns the best match first

**What to build:** Make the pick search find partial names and rank the best match first. Merge the strict and loose searches into one ranked result list.

**Background:** The develop branch ran the strict full-text search, then always ran the loose `LIKE` search and let it replace the strict results. The loose search ordered only by pick number, so the best match was not first. The split-name search ran only when both found nothing. The port runs the loose search only when the strict one finds nothing. Users now get whole-word matches only ("Tom" no longer finds "Tomlinson"), and the ASC/DESC toggle mostly has no effect. Neither behavior is good. Do not copy the develop branch. Nobody recorded this under the 4.4 decisions.

**Design:**

Run one query per search. Each Selected Pick gets a relevance tier from its name match. A lower tier number means a better match.

| Tier | Match                                                            |
| ---- | ---------------------------------------------------------------- |
| 0    | Full name equals the keywords ("Antonio Brown")                  |
| 1    | First name or last name equals the keywords ("Brown")            |
| 2    | First name or last name starts with the keywords ("Tom" → "Tomlinson") |
| 3    | First name or last name contains the keywords ("son" → "Tomlinson") |

- Order the rows by tier first. Within one tier, order by `player_pick` using the `sort` value (ASC or DESC). The sort toggle then works on every search.
- Split the keywords on spaces. A Selected Pick matches when every word matches the first name or the last name. This replaces the split-name fallback: "Antonio Brown" and "Brown Antonio" both work. Rank by the worst tier of the words.
- Keep the team and position filters as they are. Keep the `sort` whitelist in `PickSearchModel`.
- Escape `%`, `_` and `\` in the keywords before they go into `LIKE`.
- Keep `search_score` in the response because the client may read it. Fill it from the tier (a higher score for a better match).
- With no keywords, return all Selected Picks ordered by `player_pick` with the `sort` value, as now.
- Rank in SQL with `CASE`, or fetch the rows and rank them in PHP. Choose the simpler one. One draft has few Selected Picks, so speed is not a concern.
- Remove the `MATCH … AGAINST` query. Tiers 0 to 3 give a better result than the full-text score. Drop the `firstlastname_idx` full-text index on `players` only if nothing else uses it. Add a migration for that, or say in the handoff note why you kept it.

**Blocked by:** 01

**Status:** completed

- [x] One search returns ranked results. The controller no longer chains strict, loose and split searches.
- [x] A partial name ("Bro") finds "Brown" and "Tomlinson"-style names ("Tom" finds "Tomlinson").
- [x] An exact full name ranks above a partial match, even when the partial match was picked later or earlier.
- [x] Within one tier, the sort toggle changes the order. A `sort` value that is not ASC or DESC uses DESC.
- [x] "First Last" and "Last First" find the Selected Pick without the split-name fallback. Team and position filters still apply.
- [x] `%` and `_` in the keywords match only themselves.
- [x] Functional tests in `tests/Functional/PickTest.php` cover each item above. Replace `testSearchKeepsTheStrictResultsWhenThereAreSome`, which asserts the old full-text score.
- [x] Update `PHP8Rewrite.md` (the "strict full-text `MATCH`" note and the search sort note). Remove `SearchStrict`, `SearchLoose` and `SearchSplit` if the new search replaces them. Say in the handoff note what you removed.
- [x] Add an ADR document (using STE100) outlining this decision.
