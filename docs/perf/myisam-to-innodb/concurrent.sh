#!/usr/bin/env bash
# Concurrent-reader comparison, MyISAM then InnoDB, on one image and one dataset (real or 100000 are the realistic ones).
#   docs/perf/concurrent.sh [image] [dataset]
set -euo pipefail
cd "$(dirname "$0")/../.."

export PERF_DB_IMAGE="${1:-mariadb:12.3}"
DATASET="${2:-real}"
OUT="docs/perf/results/${PERF_DB_IMAGE//[:\/]/-}/$DATASET"

dc() { docker compose -p hootdraft-perf -f docs/perf/docker-compose.yml "$@"; }
bench() { dc run --rm -T runner php docs/perf/bench.php "$@"; }

echo "== concurrent: $PERF_DB_IMAGE / $DATASET"
dc down -v >/dev/null 2>&1
dc up -d db >/dev/null
mkdir -p "$OUT"
bench reset
bench migrate 20261022000000
bench seed "$DATASET" --out="$OUT"
dc restart db >/dev/null; bench concurrent myisam --out="$OUT"
bench migrate 20261028000000
bench analyze
dc restart db >/dev/null; bench concurrent innodb --out="$OUT"
