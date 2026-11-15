# Docker developer box

nginx + PHP 8.5-FPM + MariaDB 12.3 + Mailpit. The repo root is mounted into the containers, so edits are live.

```sh
docker compose up -d --build   # start (http://localhost:8000)
docker compose logs -f         # tail logs
docker compose down            # stop (add -v to also wipe the database)
```

| What | Where |
| --- | --- |
| Site | http://localhost:8000 |
| MariaDB from host | `127.0.0.1:3307`, db `hootdraft`; user `hootdraft` (app) or `root` (admin), passwords in `docker/.env` |
| MariaDB from PHP container | host `db`, port `3306`. `docker/.env` sets `DB_*` so the app and Phinx use it automatically |
| Email inbox (Mailpit) | http://localhost:8025. The `php` container sends all mail to `mailpit:1025` (set in `docker-compose.yml`, so no `.env` changes needed) |
| Composer / PHP CLI | `docker compose exec php composer ...` / `docker compose exec php php ...` |
| Symfony console | `docker compose exec php php bin/console ...` |
| Migrations | `docker compose exec php vendor/bin/phinx migrate -e production` |
| Tests | `docker compose exec php vendor/bin/phpunit` |

First time: `cp docker/.env.example docker/.env`, edit the passwords, `docker compose up -d --build`, then `composer install` and the migrations above. The `MARIADB_*` values are only read when the DB volume is first created (`down -v` to reset).

## Upgrading from the old `phpdraft` database name
The database and app user were named `phpdraft` before. They are now `hootdraft`. A volume made with the old names keeps them, because MariaDB reads `MARIADB_*` only when it creates the volume. Do one of these:
- **Keep the old names.** In `docker/.env`, set `MARIADB_DATABASE`, `MARIADB_USER`, `DB_NAME` and `DB_USER` to `phpdraft`.
- **Start fresh.** Update `docker/.env` from `.env.example`, then `docker compose down -v`, `docker compose up -d` and run the migrations. This deletes all local data.
- **Move the data.** Before you change `docker/.env`, export the old database. Then start fresh (above) and import the dump into the new database:
  ```sh
  docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" phpdraft' > phpdraft.sql
  # update docker/.env, then: docker compose down -v && docker compose up -d
  docker compose exec -T db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" hootdraft' < phpdraft.sql
  rm phpdraft.sql
  ```

## Routing (`docker/nginx/default.conf`)
Whitelist: only these are reachable, everything else (source, `vendor/`, `.env*`, `docker/`, ...) gets `index.html`.
- `/api/*` -> PHP-FPM, Symfony front controller `api/index.php`
- `/css`, `/js`, `/fonts`, `/images`, `favicon.ico` -> the file, or a real 404
- everything else -> `index.html` (AngularJS routing)
- dotfiles are always denied
