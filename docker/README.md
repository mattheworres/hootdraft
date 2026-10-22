# Docker developer box

nginx + PHP 8.5-FPM + MariaDB 12.3. The repo root is mounted into the containers, so edits are live.

```sh
docker compose up -d --build   # start (http://localhost:8000)
docker compose logs -f         # tail logs
docker compose down            # stop (add -v to also wipe the database)
```

| What | Where |
| --- | --- |
| Site | http://localhost:8000 |
| MariaDB from host | `127.0.0.1:3307`, db `phpdraft`; user `phpdraft` (app) or `root` (admin), passwords in `docker/.env` |
| MariaDB from PHP container | host `db`, port `3306`. `docker/.env` sets `DB_*` so the app and Phinx use it automatically |
| Composer / PHP CLI | `docker compose exec php composer ...` / `docker compose exec php php ...` |
| Symfony console | `docker compose exec php php bin/console ...` |
| Migrations | `docker compose exec php vendor/bin/phinx migrate -e production` |
| Tests | `docker compose exec php vendor/bin/phpunit` |

First time: `cp docker/.env.example docker/.env`, edit the passwords, `docker compose up -d --build`, then `composer install` and the migrations above. The `MARIADB_*` values are only read when the DB volume is first created (`down -v` to reset).

## Routing (`docker/nginx/default.conf`)
Whitelist: only these are reachable, everything else (source, `vendor/`, `.env*`, `docker/`, ...) gets `index.html`.
- `/api/*` -> PHP-FPM, Symfony front controller `api/index.php`
- `/css`, `/js`, `/fonts`, `/images`, `favicon.ico` -> the file, or a real 404
- everything else -> `index.html` (AngularJS routing)
- dotfiles are always denied
