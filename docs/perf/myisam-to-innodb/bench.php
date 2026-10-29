<?php

/**
 * Benchmark driver for the pro_players MyISAM vs InnoDB comparison. Run by run.sh inside the runner container:
 *   php docs/perf/bench.php <command> [argument] [--out=dir] [--sql=mode] [--label=text]
 *
 * Commands: reset | migrate <version> | seed <real|N> | analyze | schema <step> | run <phase>
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use HootDraft\Domain\Entities\ProPlayer;
use HootDraft\Domain\Repositories\ProPlayerRepository;

const WARM_BUDGET_SECONDS = 1.5;
const WARM_MAX_ITERATIONS = 1500;
const UPLOAD_ROWS = 4000;
const UPLOAD_REPEATS = 5;
const CONCURRENCY_LEVELS = [1, 4, 16, 64];
const CONCURRENCY_SECONDS = 8;

[$command, $argument, $options] = parseArgs($argv);
$out = $options['out'] ?? null;

switch ($command) {
  case 'reset':
    $pdo = connect(false);
    $pdo->exec('DROP DATABASE IF EXISTS perf');
    $pdo->exec('CREATE DATABASE perf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    break;

  case 'migrate':
    $started = hrtime(true);
    passthru(sprintf('vendor/bin/phinx migrate -c docs/perf/phinx.php -t %s 2>&1', escapeshellarg($argument)), $code);
    if ($code !== 0) {
      fwrite(STDERR, "phinx failed\n");
      exit(1);
    }
    if ($out !== null) {
      recordMeta($out, 'migrate_' . ($options['label'] ?? $argument), ['seconds' => seconds($started)]);
    }
    break;

  case 'seed':
    seed(connect(), $argument, $out);
    break;

  case 'analyze':
    connect()->query('ANALYZE TABLE pro_players, players')->fetchAll();
    break;

  case 'schema':
    applySchemaStep(connect(), $argument, $out);
    break;

  case 'run':
    run(connect(), $argument, $options['sql'] ?? 'baseline', $out);
    break;

  case 'concurrent':
    concurrent($argument, $out);
    break;

  case 'worker':
    worker($out, (float) $options['start'], (float) $options['seconds']);
    break;

  default:
    fwrite(STDERR, "unknown command\n");
    exit(1);
}

// ---------------------------------------------------------------------------------------------------------------
// Plumbing

function parseArgs(array $argv): array {
  $positional = [];
  $options = [];
  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
      [$key, $value] = explode('=', substr($arg, 2), 2);
      $options[$key] = $value;
    } else {
      $positional[] = $arg;
    }
  }

  return [$positional[0] ?? '', $positional[1] ?? '', $options];
}

function connect(bool $withDatabase = true): PDO {
  $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4%s', getenv('DB_HOST'), getenv('DB_PORT'), $withDatabase ? ';dbname=' . getenv('DB_NAME') : '');

  // The database may have just restarted
  for ($attempt = 0; $attempt < 90; $attempt++) {
    try {
      return new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
      sleep(1);
    }
  }

  throw new RuntimeException('Database never came up');
}

function seconds(int $startedNs): float {
  return round((hrtime(true) - $startedNs) / 1e9, 3);
}

function recordMeta(string $out, string $key, array $value): void {
  $file = "$out/meta.json";
  $meta = is_file($file) ? json_decode(file_get_contents($file), true) : [];
  $meta[$key] = $value;
  file_put_contents($file, json_encode($meta, JSON_PRETTY_PRINT));
}

function tableSizes(PDO $pdo): array {
  try {
    $pdo->exec('SET SESSION information_schema_stats_expiry = 0'); // MySQL 8+ caches these figures for a day
  } catch (PDOException $e) {
    // MariaDB has no such setting
  }

  $rows = $pdo->query("SELECT table_name AS t, engine, table_rows AS approx_rows, data_length AS data_bytes, index_length AS index_bytes
    FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('pro_players', 'players')")->fetchAll(PDO::FETCH_ASSOC);

  $sizes = [];
  foreach ($rows as $row) {
    $sizes[$row['t'] ?? $row['T']] = $row;
  }

  return $sizes;
}

// ---------------------------------------------------------------------------------------------------------------
// Seeding

function realPool(): array {
  $pool = [];
  foreach (glob(__DIR__ . '/../../resources/ProPlayers_*.csv') as $file) {
    preg_match('/ProPlayers_([A-Za-z]+)/', basename($file), $m);
    $league = $m[1];
    $handle = fopen($file, 'r');
    while (($data = fgetcsv($handle, 1000, ';', '"', '')) !== false) {
      $data = array_map(fn ($c) => $c === null || mb_check_encoding($c, 'UTF-8') ? $c : mb_convert_encoding($c, 'UTF-8', 'Windows-1252'), $data);
      $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
      if ($data[0] === 'Player' || $data === [null]) {
        continue;
      }
      $name = explode(',', (string) $data[0], 2);
      if (count($name) != 2 || trim($name[0]) === '' || trim($name[1]) === '') {
        continue;
      }
      $pool[$league][] = [$league, trim($name[1]), trim($name[0]), trim($data[1] ?? ''), trim($data[2] ?? '')];
    }
    fclose($handle);
  }

  return $pool;
}

function insertBatch(PDO $pdo, string $table, array $columns, array $rows): void {
  if (!$rows) {
    return;
  }
  $one = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
  $stmt = $pdo->prepare(sprintf('INSERT INTO %s (%s) VALUES %s', $table, implode(',', $columns), implode(',', array_fill(0, count($rows), $one))));
  $stmt->execute(array_merge(...$rows));
}

function seed(PDO $pdo, string $dataset, ?string $out): void {
  mt_srand(42);
  $pool = realPool();
  $flat = array_merge(...array_values($pool));
  $columns = ['league', 'first_name', 'last_name', 'position', 'team'];
  $started = hrtime(true);

  $pdo->beginTransaction();
  if ($dataset === 'real') {
    foreach (array_chunk($flat, 2000) as $chunk) {
      insertBatch($pdo, 'pro_players', $columns, $chunk);
    }
    $playerRows = 5000;
  } else {
    $total = (int) $dataset;
    $leagues = array_keys($pool);
    $weights = array_values(array_map('count', $pool));
    $weightSum = array_sum($weights);
    $batch = [];
    for ($i = 0; $i < $total; $i++) {
      // Pick a league in proportion to the real league sizes, then recombine real first and last names within it
      $pick = mt_rand(1, $weightSum);
      foreach ($leagues as $idx => $league) {
        $pick -= $weights[$idx];
        if ($pick <= 0) {
          break;
        }
      }
      $a = $pool[$league][mt_rand(0, $weights[$idx] - 1)];
      $b = $pool[$league][mt_rand(0, $weights[$idx] - 1)];
      $batch[] = [$league, $a[1], $b[2], $a[3], $a[4]];
      if (count($batch) === 2000) {
        insertBatch($pdo, 'pro_players', $columns, $batch);
        $batch = [];
      }
    }
    insertBatch($pdo, 'pro_players', $columns, $batch);
    $playerRows = min($total, 500000);
  }

  // Picks for the MATCH() workload: 250 per draft, names drawn from the same pool
  $batch = [];
  $pickColumns = ['manager_id', 'draft_id', 'first_name', 'last_name', 'team', 'position', 'pick_time', 'pick_duration', 'player_counter', 'player_round', 'player_pick'];
  for ($i = 0; $i < $playerRows; $i++) {
    $row = $flat[mt_rand(0, count($flat) - 1)];
    $other = $flat[mt_rand(0, count($flat) - 1)];
    $batch[] = [mt_rand(1, 12), intdiv($i, 250) + 1, $row[1], $other[2], substr($row[4], 0, 3), substr($row[3], 0, 4), '2026-01-01 12:00:00', 30, $i % 250 + 1, intdiv($i % 250, 12) + 1, $i % 250 + 1];
    if (count($batch) === 2000) {
      insertBatch($pdo, 'players', $pickColumns, $batch);
      $batch = [];
    }
  }
  insertBatch($pdo, 'players', $pickColumns, $batch);
  $pdo->commit();
  $pdo->query('ANALYZE TABLE pro_players, players')->fetchAll();
  $seedSeconds = seconds($started);

  // Search terms are fixed here, once, so every later phase searches for exactly the same things
  $counts = $pdo->query('SELECT league, COUNT(*) c FROM pro_players GROUP BY league ORDER BY c DESC')->fetchAll(PDO::FETCH_KEY_PAIR);
  $big = array_key_first($counts);
  $small = array_key_last($counts);

  $terms = ['leagues' => ['big' => $big, 'small' => $small], 'league_counts' => $counts, 'per_league' => []];
  foreach (['big' => $big, 'small' => $small] as $class => $league) {
    $pair = $pdo->prepare('SELECT team, position FROM pro_players WHERE league = ? GROUP BY team, position ORDER BY COUNT(*) DESC, team, position LIMIT 1');
    $pair->execute([$league]);
    $teamPos = $pair->fetch(PDO::FETCH_ASSOC);
    $rare = $pdo->prepare('SELECT last_name FROM pro_players WHERE league = ? GROUP BY last_name ORDER BY COUNT(*), last_name LIMIT 1 OFFSET 50');
    $rare->execute([$league]);
    $full = $pdo->prepare('SELECT first_name, last_name FROM pro_players WHERE league = ? ORDER BY pro_player_id LIMIT 1 OFFSET 7');
    $full->execute([$league]);
    $name = $full->fetch(PDO::FETCH_ASSOC);
    $terms['per_league'][$class] = ['team' => $teamPos['team'], 'position' => $teamPos['position'], 'rare_last' => $rare->fetchColumn(), 'first' => $name['first_name'], 'last' => $name['last_name']];
  }
  $terms['drafts'] = [1, max(1, intdiv($playerRows, 250) / 2 | 0)];

  if ($out !== null) {
    file_put_contents("$out/terms.json", json_encode($terms, JSON_PRETTY_PRINT));
    recordMeta($out, 'seed', ['dataset' => $dataset, 'seconds' => $seedSeconds, 'version' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'sizes' => tableSizes($pdo)]);
  }
}

// ---------------------------------------------------------------------------------------------------------------
// Schema candidates (stacked)

function applySchemaStep(PDO $pdo, string $step, ?string $out): void {
  $sql = match ($step) {
    'varchar' => 'ALTER TABLE pro_players MODIFY league VARCHAR(16) NOT NULL, MODIFY first_name VARCHAR(100) NOT NULL, MODIFY last_name VARCHAR(100) NOT NULL,
      MODIFY position VARCHAR(16) NOT NULL, MODIFY team VARCHAR(16) NOT NULL, DROP INDEX league_idx, ADD INDEX league_idx (league)',
    'composite' => 'ALTER TABLE pro_players DROP INDEX league_idx, ADD INDEX league_last_idx (league, last_name), ADD INDEX league_first_idx (league, first_name)',
    'drop_fulltext' => 'ALTER TABLE pro_players DROP INDEX firstname_idx, DROP INDEX lastname_idx',
    'general_ci' => 'ALTER TABLE pro_players CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci',
  };

  if ($step === 'varchar') {
    $max = $pdo->query('SELECT MAX(CHAR_LENGTH(first_name)), MAX(CHAR_LENGTH(last_name)), MAX(CHAR_LENGTH(team)), MAX(CHAR_LENGTH(position)), MAX(CHAR_LENGTH(league)) FROM pro_players')->fetch(PDO::FETCH_NUM);
    if ($max[0] > 100 || $max[1] > 100 || $max[2] > 16 || $max[3] > 16 || $max[4] > 16) {
      throw new RuntimeException('Data would not fit the varchar sizes: ' . implode(',', $max));
    }
  }

  $started = hrtime(true);
  $pdo->exec($sql);
  $alter = seconds($started);
  $pdo->query('ANALYZE TABLE pro_players')->fetchAll();

  if ($out !== null) {
    recordMeta($out, "schema_$step", ['alter_seconds' => $alter, 'sizes' => tableSizes($pdo)]);
  }
}

// ---------------------------------------------------------------------------------------------------------------
// Workloads

/**
 * Each query is [id, class, kind, callable(PDO): array{ids, sqls}, countCallable]. A query runs the app's own SQL for the
 * mode; "ordered", "prefix", "prefix_fallback" and "fulltext" are the candidate rewrites.
 */
function buildQueries(string $mode, array $terms): array {
  $queries = [];

  foreach (['big', 'small'] as $class) {
    $league = $terms['leagues'][$class];
    $t = $terms['per_league'][$class];

    $autocomplete = ['prefix2' => 'sm', 'prefix3' => 'joh', 'common_substring' => 'son', 'very_common' => 'an', 'rare' => $t['rare_last'], 'no_match' => 'zzqx'];
    foreach ($autocomplete as $shape => $term) {
      $queries[] = ["autocomplete.$shape.$class", $class, 'autocomplete', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, null, null, $term, $count)];
    }

    $queries[] = ["autocomplete.multiword.$class", $class, 'autocomplete', function (PDO $pdo, bool $count = false) use ($mode, $league, $t) {
      // Same flow as ProPlayerService::SearchPlayers: whole string first, then the first/last split when nothing matched
      $term = $t['first'] . ' ' . $t['last'];
      $result = nameSearch($pdo, $mode, $league, null, null, $term, $count);
      if (($count ? $result['total'] : count($result['ids'])) == 0) {
        $second = nameSearch($pdo, $mode, $league, $t['first'], $t['last'], null, $count);
        $second['sqls'] = array_merge($result['sqls'], $second['sqls']);

        return $second;
      }

      return $result;
    }];

    $queries[] = ["assumed_name.common.$class", $class, 'assumed_name', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, 'jo', 'smi', null, $count, true)];
    $queries[] = ["assumed_name.no_match.$class", $class, 'assumed_name', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, 'zzq', 'yyx', null, $count, true)];

    $queries[] = ["manual.last.$class", $class, 'manual', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, null, 'son', null, $count, true)];
    $queries[] = ["manual.last_position.$class", $class, 'manual', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, null, 'son', null, $count, true, null, $t['position'])];
    $queries[] = ["manual.team_position.$class", $class, 'manual', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, null, null, null, $count, true, $t['team'], $t['position'])];
    $queries[] = ["manual.first_last_team.$class", $class, 'manual', fn (PDO $pdo, bool $count = false) => nameSearch($pdo, $mode, $league, 'jo', 'son', null, $count, true, $t['team'])];
  }

  return $queries;
}

/**
 * $term alone = SearchPlayers (first OR last). $first/$last = SearchPlayersByAssumedName or SearchPlayersManual. With
 * $count it returns the number of rows that match with no LIMIT, which shows recall changes the LIMIT would hide.
 */
function nameSearch(PDO $pdo, string $mode, string $league, ?string $first, ?string $last, ?string $term, bool $count, bool $and = false, ?string $team = null, ?string $position = null): array {
  $run = function (string $pattern) use ($pdo, $mode, $league, $first, $last, $term, $count, $and, $team, $position) {
    $params = [':league' => $league];
    $where = 'league = :league';
    $useFulltext = $mode === 'fulltext';

    $predicate = function (string $column, string $key, string $value) use (&$params, $pattern, $useFulltext) {
      if ($useFulltext) {
        $words = preg_split('/\s+/', trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $value)), -1, PREG_SPLIT_NO_EMPTY);
        $params[":$key"] = $words ? '+' . implode('* +', $words) . '*' : '+';

        return "MATCH($column) AGAINST(:$key IN BOOLEAN MODE)";
      }
      $params[":$key"] = sprintf($pattern, $value);

      return "$column LIKE :$key";
    };

    if ($term !== null) {
      $where .= ' AND (' . $predicate('first_name', 't1', $term) . ' OR ' . $predicate('last_name', 't2', $term) . ')';
    } else {
      $parts = [];
      if ($first !== null) {
        $parts[] = $predicate('first_name', 'first_name', $first);
      }
      if ($last !== null) {
        $parts[] = $predicate('last_name', 'last_name', $last);
      }
      if ($parts) {
        $where .= ' AND ' . implode(' AND ', $parts);
      }
    }

    if ($team !== null) {
      $where .= ' AND team = :team';
      $params[':team'] = $team;
    }
    if ($position !== null) {
      $where .= ' AND position = :position';
      $params[':position'] = $position;
    }

    if ($count) {
      $sql = "SELECT COUNT(*) FROM pro_players WHERE $where";
    } else {
      $order = $mode === 'ordered' ? ' ORDER BY last_name, first_name, pro_player_id' : '';
      $sql = "SELECT * FROM pro_players WHERE $where$order LIMIT 15";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($count) {
      return ['total' => (int) $stmt->fetchColumn(), 'ids' => [], 'sqls' => [[$sql, $params]]];
    }

    return ['ids' => array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'pro_player_id')), 'sqls' => [[$sql, $params]]];
  };

  if ($mode === 'prefix') {
    return $run('%s%%');
  }

  if ($mode === 'prefix_fallback' && !$count) {
    $result = $run('%s%%');
    if (count($result['ids']) >= 15) {
      return $result;
    }
    $second = $run('%%%s%%');
    $merged = array_slice(array_values(array_unique(array_merge($result['ids'], $second['ids']))), 0, 15);

    return ['ids' => $merged, 'sqls' => array_merge($result['sqls'], $second['sqls'])];
  }

  return $run('%%%s%%');
}

function matchQueries(array $terms): array {
  $drafts = $terms['drafts'];
  $searches = [
    'common_name' => ['john', $drafts[0], null, null],
    'rare_name' => [$terms['per_league']['big']['rare_last'], $drafts[0], null, null],
    'two_words' => ['john smith', $drafts[1], null, null],
    'no_match' => ['zzqx', $drafts[1], null, null],
    'with_team' => ['john', $drafts[1], $terms['per_league']['big']['team'], null],
  ];

  $queries = [];
  foreach ($searches as $shape => [$keywords, $draft, $team, $position]) {
    $queries[] = ["pick_search.$shape", 'match', 'pick_search', function (PDO $pdo, bool $count = false) use ($keywords, $draft, $team) {
      // Same SQL as the old PickSearchRepository::SearchStrict (removed, see docs/adr/0003)
      $sql = 'SELECT p.*, m.manager_name, MATCH (p.first_name, p.last_name) AGAINST (?) as search_score '
        . 'FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE MATCH (p.first_name, p.last_name) AGAINST (?) AND p.draft_id = ? ';
      $params = [$keywords, $keywords, $draft];
      if ($team !== null) {
        $sql .= 'AND p.team = ? ';
        $params[] = $team;
      }
      $sql .= 'AND p.pick_time IS NOT NULL ORDER BY search_score ASC, p.player_pick ASC';
      if ($count) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ($sql) x");
        $stmt->execute($params);

        return ['total' => (int) $stmt->fetchColumn(), 'ids' => [], 'sqls' => [[$sql, $params]]];
      }
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);

      return ['ids' => array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'player_id')), 'sqls' => [[$sql, $params]]];
    }];
  }

  return $queries;
}

function percentile(array $sorted, float $p): float {
  $idx = (int) ceil($p / 100 * count($sorted)) - 1;

  return round($sorted[max(0, min($idx, count($sorted) - 1))], 3);
}

function explainFirst(PDO $pdo, array $sqls): array {
  [$sql, $params] = $sqls[0];

  try {
    $pdo->exec('SET SESSION explain_format = TRADITIONAL'); // MySQL 9 defaults to a one-column TREE plan
  } catch (PDOException $e) {
    // MariaDB and MySQL 8.0 have no such variable and already print the table
  }

  $stmt = $pdo->prepare("EXPLAIN $sql");
  $stmt->execute($params);

  return array_map(fn ($r) => array_intersect_key(array_change_key_case($r), array_flip(['select_type', 'table', 'type', 'possible_keys', 'key', 'rows', 'extra'])), $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function measure(PDO $pdo, array $query): array {
  [$id, $class, $kind, $fn] = $query;

  $started = hrtime(true);
  $first = $fn($pdo);
  $cold = (hrtime(true) - $started) / 1e6;

  return ['id' => $id, 'class' => $class, 'kind' => $kind, 'cold_ms' => round($cold, 3), 'first' => $first];
}

function run(PDO $pdo, string $phase, string $mode, string $out): void {
  $terms = json_decode(file_get_contents("$out/terms.json"), true);
  $queries = buildQueries($mode, $terms);
  if ($mode === 'baseline') {
    $queries = array_merge($queries, matchQueries($terms));
  }

  // The first pass after a restart is the cold one: every query once, in order
  $results = [];
  foreach ($queries as $order => $query) {
    $r = measure($pdo, $query);
    $r['cold_order'] = $order;
    $results[$query[0]] = $r;
  }

  $report = [];
  foreach ($queries as $query) {
    $id = $query[0];
    $fn = $query[3];
    $cold = $results[$id];

    for ($i = 0; $i < 3; $i++) {
      $fn($pdo);
    }

    $times = [];
    $budgetEnd = hrtime(true) + (int) (WARM_BUDGET_SECONDS * 1e9);
    while (count($times) < WARM_MAX_ITERATIONS && (hrtime(true) < $budgetEnd || count($times) < 5)) {
      $t = hrtime(true);
      $fn($pdo);
      $times[] = (hrtime(true) - $t) / 1e6;
    }
    sort($times);

    $count = $fn($pdo, true);
    $report[] = [
      'id' => $id,
      'class' => $cold['class'],
      'kind' => $cold['kind'],
      'cold_ms' => $cold['cold_ms'],
      'cold_order' => $cold['cold_order'],
      'iterations' => count($times),
      'p50_ms' => percentile($times, 50),
      'p95_ms' => percentile($times, 95),
      'p99_ms' => percentile($times, 99),
      'mean_ms' => round(array_sum($times) / count($times), 3),
      'ids' => $cold['first']['ids'],
      'total_matches' => $count['total'],
      'sql' => $cold['first']['sqls'][0][0],
      'explain' => explainFirst($pdo, $cold['first']['sqls']),
    ];
  }

  $payload = [
    'phase' => $phase,
    'sql_mode' => $mode,
    'version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
    'sizes' => tableSizes($pdo),
    'queries' => $report,
  ];

  if ($mode === 'baseline') {
    verifyAgainstRepository($pdo, $terms, $report);
    $payload['upload'] = uploadTest($pdo);
  }

  $file = $out . '/' . preg_replace('/[^A-Za-z0-9_.-]+/', '_', $phase) . '.json';
  file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
  fwrite(STDERR, "wrote $file\n");
}

/** Guards against the harness SQL drifting from the app's: the repository must return the same ids for the same search. */
function verifyAgainstRepository(PDO $pdo, array $terms, array $report): void {
  $repository = new ProPlayerRepository($pdo);
  $league = $terms['leagues']['big'];
  $byId = array_column($report, null, 'id');

  foreach (['prefix3' => 'joh', 'common_substring' => 'son', 'no_match' => 'zzqx'] as $shape => $term) {
    $expected = array_map(fn ($p) => (int) $p->pro_player_id, $repository->SearchPlayers($league, $term));
    $actual = $byId["autocomplete.$shape.big"]['ids'];
    if ($expected !== $actual) {
      throw new RuntimeException("Harness SQL for autocomplete.$shape.big no longer matches ProPlayerRepository::SearchPlayers");
    }
  }

  $expected = array_map(fn ($p) => (int) $p->pro_player_id, $repository->SearchPlayersByAssumedName($league, 'jo', 'smi'));
  if ($expected !== $byId['assumed_name.common.big']['ids']) {
    throw new RuntimeException('Harness SQL for assumed_name.common.big no longer matches ProPlayerRepository::SearchPlayersByAssumedName');
  }
}

/** Times what the CSV upload does: delete a league and insert its players again, in one transaction, via the real repository. */
function uploadTest(PDO $pdo): array {
  $repository = new ProPlayerRepository($pdo);
  $columns = ['league', 'first_name', 'last_name', 'position', 'team'];

  $players = [];
  $rows = [];
  for ($i = 0; $i < UPLOAD_ROWS; $i++) {
    $player = new ProPlayer();
    $player->league = 'UPLOAD';
    $player->first_name = 'First' . $i;
    $player->last_name = 'Last' . ($i * 7);
    $player->position = 'P';
    $player->team = 'TST';
    $players[] = $player;
    $rows[] = [$player->league, $player->first_name, $player->last_name, $player->position, $player->team];
  }

  $pdo->exec("DELETE FROM pro_players WHERE league = 'UPLOAD'");
  foreach (array_chunk($rows, 2000) as $chunk) {
    insertBatch($pdo, 'pro_players', $columns, $chunk);
  }

  $times = [];
  for ($i = 0; $i < UPLOAD_REPEATS; $i++) {
    foreach ($players as $player) {
      $player->pro_player_id = null;
    }
    $started = hrtime(true);
    $pdo->beginTransaction();
    $repository->SaveProPlayers('UPLOAD', $players);
    $pdo->commit();
    $times[] = (hrtime(true) - $started) / 1e6;
  }
  sort($times);
  $pdo->exec("DELETE FROM pro_players WHERE league = 'UPLOAD'");

  return ['rows' => UPLOAD_ROWS, 'repeats' => UPLOAD_REPEATS, 'median_ms' => round($times[intdiv(count($times), 2)], 1), 'min_ms' => round($times[0], 1), 'max_ms' => round(end($times), 1)];
}

// ---------------------------------------------------------------------------------------------------------------
// Concurrent readers

/**
 * Closed-loop load: N worker processes each fire the name-search mix (every autocomplete, assumed-name and manual query,
 * both league sizes, picked at random) back to back for a few seconds. That is saturation, harsher than commissioners
 * with think time, so it shows where each engine's latency starts to climb and how much it can serve.
 */
function concurrent(string $phase, string $out): void {
  $levels = [];
  foreach (CONCURRENCY_LEVELS as $workers) {
    $start = microtime(true) + 2 + $workers * 0.05; // workers connect first, then all begin together
    $procs = [];
    for ($i = 0; $i < $workers; $i++) {
      $cmd = sprintf('php %s worker --out=%s --start=%F --seconds=%d', escapeshellarg(__FILE__), escapeshellarg($out), $start, CONCURRENCY_SECONDS);
      $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => STDERR], $pipes[$i]);
    }

    $times = [];
    foreach ($procs as $i => $proc) {
      $times = array_merge($times, json_decode(stream_get_contents($pipes[$i][1]), true));
      proc_close($proc);
    }
    sort($times);

    $levels[] = ['workers' => $workers, 'queries' => count($times), 'queries_per_second' => round(count($times) / CONCURRENCY_SECONDS),
      'p50_ms' => percentile($times, 50), 'p95_ms' => percentile($times, 95), 'p99_ms' => percentile($times, 99), 'max_ms' => round(end($times), 1)];
  }

  $file = $out . '/concurrent_' . preg_replace('/[^A-Za-z0-9_.-]+/', '_', $phase) . '.json';
  file_put_contents($file, json_encode(['phase' => $phase, 'seconds' => CONCURRENCY_SECONDS, 'levels' => $levels], JSON_PRETTY_PRINT));
  fwrite(STDERR, "wrote $file\n");
}

function worker(string $out, float $start, float $seconds): void {
  $pdo = connect();
  $terms = json_decode(file_get_contents("$out/terms.json"), true);
  $queries = array_values(array_filter(buildQueries('baseline', $terms), fn ($q) => true));
  mt_srand(getmypid());

  while (microtime(true) < $start) {
    usleep(1000);
  }

  $times = [];
  $end = $start + $seconds;
  while (microtime(true) < $end) {
    $fn = $queries[mt_rand(0, count($queries) - 1)][3];
    $t = hrtime(true);
    $fn($pdo);
    $times[] = round((hrtime(true) - $t) / 1e6, 3);
  }

  echo json_encode($times);
}
