# Pick search is one ranked query

The develop branch ran three searches in a chain: a strict full-text `MATCH ... AGAINST`, a loose `LIKE '%word%'`, and a split first/last name search. The loose result replaced the strict result. It had no ranking.
The port ran the loose search only when the strict search found nothing. It found whole words only ("Tom" did not find "Tomlinson").

The search is now one `LIKE` query. The code gives each Selected Pick a tier. A lower tier number is a better match.
Rows sort by tier first. Inside one tier, the `sort` value (ASC or DESC) orders by `player_pick`.

| Tier | Match                                                                  |
| ---- | ---------------------------------------------------------------------- |
| 0    | Full name equals the keywords ("Antonio Brown")                        |
| 1    | First name or last name equals the keywords ("Brown")                  |
| 2    | First name or last name starts with the keywords ("Tom" → "Tomlinson") |
| 3    | First name or last name contains the keywords ("son" → "Tomlinson")    |

Split the keywords on spaces. A Selected Pick matches when every word is in the first name or the last name, in any order. The worst tier of the words is the tier of the Selected Pick.
`search_score` in the response is 3 minus the tier. A higher score is a better match.

Consequences:

- A partial name finds a Selected Pick, and the best match is first. The ASC/DESC toggle works on every search.
- The split-name fallback is not needed. `SearchStrict`, `SearchLoose` and `SearchSplit` are removed.
- Migration `20261030000000_drop_players_name_fulltext_index` drops the `firstlastname_idx` full-text index from `players`. Nothing uses it.
- A draft has few Selected Picks, so the code ranks the rows in PHP. If a draft can hold many thousand Selected Picks, measure the search again.
- `docs/perf/myisam-to-innodb/bench.php` still holds the old strict query. It records the engine change. Do not use it for the new search.
- The Commissioner's Pro Player search (`ProPlayerRepository`) is a different search. It is not changed.
