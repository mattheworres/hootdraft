# pro_players uses InnoDB, not MyISAM

`pro_players` was MyISAM for years because MyISAM reads faster on very large tables.
We changed it to InnoDB so that the CSV upload can use a transaction. A failed upload no longer leaves a league empty.
A benchmark (`docs/perf/README.md`) shows that InnoDB is 15% to 40% faster for the real HootDraft data and returns the same results.
MyISAM does more queries each second only with 16 or more readers at the same time. HootDraft does not reach that load.
Do not change the table back to MyISAM for speed.
