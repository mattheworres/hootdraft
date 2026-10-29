# MariaDB 12.3 is the primary database. MySQL 8.4 and 9 are supported.

The old minimum was MySQL 5.6. We now test on three servers: MariaDB 12.3, MySQL 8.4 (LTS) and MySQL 9 (9.7.4).
All 241 tests pass on each server, and the search benchmark gives the same results on each server.
The application uses no feature that only one server has. The development box and the benchmark use MariaDB 12.3.
MySQL 8.0 and older are not tested. A new server version needs a full test run before it is supported.
