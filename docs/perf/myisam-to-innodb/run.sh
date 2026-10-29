#!/usr/bin/env bash
# One full comparison on one database image and one dataset.
#   docs/perf/run.sh [image] [dataset]      e.g. docs/perf/run.sh mariadb:12.3 real
# dataset: real | 10000 | 100000 | 1000000. Results land in docs/perf/results/<image>/<dataset>/.
set -euo pipefail
cd "$(dirname "$0")/../.."

export PERF_DB_IMAGE="${1:-mariadb:12.3}"
DATASET="${2:-real}"
SLUG="${PERF_DB_IMAGE//[:\/]/-}"
OUT="docs/perf/results/$SLUG/$DATASET"

dc() { docker compose -p hootdraft-perf -f docs/perf/docker-compose.yml "$@"; }
bench() { dc run --rm -T runner php docs/perf/bench.php "$@"; }
restart() { dc restart db >/dev/null; }   # a restart empties the buffer pool, so the next bench has a real cold pass

dc down -v >/dev/null 2>&1   # fresh data volume, so one image never inherits another image's files
dc up -d db >/dev/null
mkdir -p "$OUT"

echo "== $PERF_DB_IMAGE / $DATASET"
bench reset
bench migrate 20261022000000          # everything up to, but not including, the InnoDB conversion
bench seed "$DATASET" --out="$OUT"

restart; bench run myisam --out="$OUT"
bench migrate 20261028000000 --out="$OUT" --label=convert   # the real conversion migration, timed
bench analyze
restart; bench run innodb --out="$OUT"

# SQL-only candidates, on the converted table (FULLTEXT indexes still present)
for sql in ordered prefix prefix_fallback fulltext; do
  restart; bench run "innodb+$sql" --sql="$sql" --out="$OUT"
done

# Schema candidates, stacked: each step keeps the previous ones
for step in varchar composite drop_fulltext general_ci; do
  bench schema "$step" --out="$OUT"
  restart; bench run "schema:$step" --out="$OUT"
done

# SQL-only candidates again on the tuned schema
for sql in ordered prefix prefix_fallback; do
  restart; bench run "tuned+$sql" --sql="$sql" --out="$OUT"
done

echo "== done: $OUT"
