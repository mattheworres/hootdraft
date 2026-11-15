# pro_players search benchmark

This document is written in ASD-STE100 Simplified Technical English.

## Purpose

Commissioners search the `pro_players` table with text input.
The table changed from MyISAM to InnoDB so that the CSV upload can use a transaction.
This test checks if the change made the search slower.
It also looks for changes that make the search faster.

## Result in short

1. InnoDB is not slower than MyISAM for the real HootDraft data. It is 15% to 40% faster.
2. Both engines return the same rows, in the same order.
3. With many readers at the same time, InnoDB does fewer queries each second than MyISAM. HootDraft does not reach this load.
4. Three changes make the search faster on small tables: composite indexes, the `utf8mb4_general_ci` collation and no FULLTEXT indexes. Migration `20261029000000_tune_pro_players_for_search` makes these changes.
5. The application works on MariaDB 12.3, MySQL 8.4 and MySQL 9. All 241 tests pass on each server.

## How the test works

The test uses a Docker container that you can delete. It does not use the development database.
The values in the tables in this document come from `RESULTS.md`. A percentage is the change in the time, unless the text says otherwise.

| Item | Value |
|---|---|
| Servers | `mariadb:12.3`, `mysql:8.4`, `mysql:9` (9.7.4) |
| Data | The real CSV files (about 10,000 rows). Made-up data with 10,000, 100,000 and 1,000,000 rows. |
| Search SQL | The same SQL as `ProPlayerRepository`. The script checks this on each run. |
| Search types | Autocomplete, assumed first and last name, and manual search. Each type has several term shapes. |
| Times | p50, p95 and p99 in ms. Warm runs and one cold run after a restart of the container. |
| Before and after | The script uses the real migrations. It creates the MyISAM table, measures it, runs `ConvertProPlayersToInnodb`, and measures it again. |
| Pick search | The `MATCH ... AGAINST` search on the `players` table. It does not change between the two runs. It shows the measurement noise. |

A query is slower when its p95 time is more than 20% longer **and** more than 5 ms longer.

## Results: engine change

| Check | Result |
|---|---|
| Slower queries on the real data | None |
| Search time on the real data | Less than 12 ms for all queries on both engines |
| Search time, 100,000 rows | Scan queries take 30 ms to 60 ms on InnoDB. InnoDB is 29% to 41% faster than MyISAM at p95. |
| Search time, 1,000,000 rows | Scan queries take 0.3 s to 0.8 s on InnoDB. InnoDB is 14% to 37% faster than MyISAM at p95. |
| Rows, order and match counts | Identical on all servers and all data sizes |
| Measurement noise | The pick-search queries change by up to 19% between runs. Ignore differences smaller than this. |
| CSV upload, 4,000 rows | MariaDB: 460 ms to 590 ms for both engines. MySQL: MyISAM 2.7 s to 3.1 s, InnoDB 0.5 s. |
| Conversion time | 0.2 s to 0.4 s on the real data. 6.6 s (MariaDB) or 11 s (MySQL) for 1,000,000 rows. |

One query was flagged as slower: `autocomplete.multiword.small` on MySQL 8.4 with 1,000,000 rows.
Its p95 time went from 85 ms to 102 ms. Its p50 time went from 77 ms to 65 ms.
This is a delay on a small number of runs. It is not a slowdown.

The binary log can cause the slow MySQL upload on MyISAM. This is not verified.

## Results: concurrent readers

N workers run searches without a pause for 8 seconds.
This load is much higher than the load of real commissioners.
The Docker machine has 10 CPUs.

MariaDB 12.3, real data:

| Workers | MyISAM queries/s | InnoDB queries/s | MyISAM p95 (ms) | InnoDB p95 (ms) |
|---|---|---|---|---|
| 1 | 422 | 867 | 5.5 | 3.1 |
| 4 | 1,704 | 1,590 | 6.4 | 7.5 |
| 16 | 2,387 | 1,362 | 24.3 | 40.6 |
| 64 | 2,410 | 1,420 | 94.9 | 156.2 |

MySQL 8.4, MySQL 9 and the 100,000-row data show the same pattern. See `RESULTS.md`.

1. With 1 worker, InnoDB does about 2 times more queries each second.
2. With 4 workers, InnoDB does 7% to 24% fewer queries each second. The p95 times are close.
3. With 16 or more workers, InnoDB does about 40% fewer queries each second. Its p95 time is 65% to 100% longer.
4. The cause is not known. The cost of InnoDB locks and page latches can be the cause. This is not verified.

What this means for HootDraft:

- On the real data, InnoDB handles about 1,400 searches each second at full load.
- 100 commissioners that search once every 3 seconds make about 33 searches each second. This is about 2% of the limit.
- At this load, the system works in the 1-worker region, where InnoDB is faster.
- MyISAM gives a gain only when many workers read a very large table. HootDraft tables are small, so this gain does not apply.

## Results: possible improvements

Values are the change in the total p50 time of the 26 name-search queries. The reference is the converted InnoDB table.

| Change | Real data | 100,000 rows | 1,000,000 rows | Search results |
|---|---|---|---|---|
| `varchar` columns and composite indexes `(league, last_name)` and `(league, first_name)` | -20% to -33% | -17% | +25% (MariaDB), -1% to +6% (MySQL) | No change |
| `utf8mb4_general_ci` collation, added after the indexes | 3 to 13 points faster | 5 points faster | 4 to 7 points faster | No change |
| Remove the two FULLTEXT indexes | Upload about 7% faster | Search: no change | Search: no change | No change |
| Add `ORDER BY last_name, first_name` | +49% to +92% | +97% | +213% to +351% | No change |
| Match the start of the name (`term%`) | +9% to +15% | +23% | +51% to +63% | **Loses matches** in 12 to 14 of 26 queries |
| Match the start, then search the full name | +96% to +100% | +110% | +144% to +154% | No change |
| Match the start, on the tuned schema | -41% to -84% | -97% | -99% (MariaDB), +6% to +10% (MySQL) | **Loses matches** |
| FULLTEXT search | +364% (MariaDB), +524% to +554% (MySQL) | +34% | -5% to -10% | **Loses matches** in 12 to 14 of 26 queries |

Notes:

- `ORDER BY` makes the database find and sort all matches. Without it, the search stops at 15 rows.
- The search uses `LIKE '%term%'`. The FULLTEXT indexes on `pro_players` are not used by the search.
- The `term%` search does not find a name by a part from the middle. Do not use it.
- MariaDB uses the composite indexes for the `term%` search. MySQL does not.
- The unordered `LIMIT 15` gave the same rows on both engines in all tests. The order is not guaranteed.

## Decisions

1. Keep InnoDB for `pro_players`. See `docs/adr/0001-pro-players-uses-innodb.md`.
2. Make the `varchar`, composite index and `general_ci` changes. Migration `20261029000000_tune_pro_players_for_search` does this.
3. Remove the two FULLTEXT indexes. The search never uses them. The same migration does this, and its `down()` method adds them again.
4. Do not add `ORDER BY`. Do not use the `term%` search.
5. Support MariaDB 12.3, MySQL 8.4 and MySQL 9. See `docs/adr/0002-supported-databases.md`.

The `varchar` change is in the migration because a full-length index needs it.
A `text` column needs a prefix length in an index. That design was not tested.
The tested design used `varchar` columns.

The `general_ci` collation compares letters differently from `unicode_ci` in rare cases.
For example, `unicode_ci` treats `ß` and `ss` as equal. `general_ci` does not.
Searches still ignore case and most accents.

## Versions

| Server | Tests | Benchmark | Note |
|---|---|---|---|
| MariaDB 12.3 | 241 pass | Slightly faster than MySQL. Uses the composite indexes for more query shapes. | Primary target |
| MySQL 8.4 (LTS) | 241 pass | Same results. Slower alter on 1,000,000 rows (11 s). | Supported |
| MySQL 9 (9.7.4) | 241 pass | Same results as 8.4. | Supported. `EXPLAIN` prints a tree by default. The script sets `explain_format = TRADITIONAL`. |
| MySQL 8.0 | Not tested | Not tested | Not tested |

No version has a problem that you must avoid.

## Limits of the test

- The tests ran on one laptop in Docker. Compare numbers inside one table only.
- A cold run clears the buffer pool. It does not clear the host page cache. Use warm numbers.
- The concurrent test uses workers without a pause. Real users pause between searches.
- The query plans for MySQL 9 at 1,000,000 rows are empty. The first MySQL 9 run used a plan format that the script could not read. The script is now corrected. Only the MySQL 9 run on the real data was repeated.
- The production server is not known. The test assumes MariaDB 12.3.

## How to run

```
docs/perf/run.sh [image] [dataset]          # engine comparison and improvements
docs/perf/concurrent.sh [image] [dataset]   # concurrent readers
php docs/perf/report.php                    # builds RESULTS.md from results/
```

- `image`: `mariadb:12.3` (default), `mysql:8.4` or `mysql:9`.
- `dataset`: `real`, `10000`, `100000` or `1000000`.
- Use `bash` to run the scripts. They use bash features.
- A 1,000,000-row run takes about 20 minutes. A real-data run takes about 7 minutes.

## Files

| File | Purpose |
|---|---|
| `docker-compose.yml` | Throwaway database and runner container |
| `run.sh` | Full comparison for one image and one dataset |
| `concurrent.sh` | Concurrent-reader test for one image and one dataset |
| `bench.php` | Seed, measure, schema steps and workers |
| `report.php` | Builds `RESULTS.md` |
| `RESULTS.md` | All numbers |
| `results/` | Raw JSON for each run |
