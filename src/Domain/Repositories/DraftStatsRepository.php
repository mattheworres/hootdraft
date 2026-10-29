<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\DraftStats;

class DraftStatsRepository {
  public function __construct(
    private \PDO $pdo,
    private DraftDataRepository $draftData,
    private DraftRepository $drafts,
  ) {
  }

  public function LoadDraftStats($draft_id) {
    $stats = new DraftStats();

    $load_stmt = $this->pdo->prepare("SELECT * FROM draft_stats WHERE draft_id = ? LIMIT 1");
    $load_stmt->bindParam(1, $draft_id);

    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $stats);

    if (!$load_stmt->execute()) {
      throw new \Exception("Unable to load draft stats");
    }

    if ($load_stmt->rowCount() == 0) {
      return null;
    }

    if (!$load_stmt->fetch()) {
      throw new \Exception("Error while loading draft stats");
    }

    //Because this cant be changed in PDO, we need to manually make these ints
    $stats->drafting_time_seconds = (int)$stats->drafting_time_seconds;
    $stats->longest_avg_pick_seconds = (int)$stats->longest_avg_pick_seconds;
    $stats->shortest_avg_pick_seconds = (int)$stats->shortest_avg_pick_seconds;
    $stats->longest_single_pick_seconds = (int)$stats->longest_single_pick_seconds;
    $stats->shortest_single_pick_seconds = (int)$stats->shortest_single_pick_seconds;
    $stats->average_pick_seconds = (int)$stats->average_pick_seconds;
    $stats->longest_round_seconds = (int)$stats->longest_round_seconds;
    $stats->shortest_round_seconds = (int)$stats->shortest_round_seconds;
    $stats->average_round_seconds = (int)$stats->average_round_seconds;

    return $stats;
  }

  /**
   * Replaces the draft's stats row as one unit: a failure part-way leaves the previous row in place. Joins the
   * caller's transaction when one is open (the caller then owns the commit and the cache flush).
   */
  public function CalculateDraftStatistics(Draft $draft) {
    $ownsTransaction = !$this->pdo->inTransaction();

    try {
      if ($ownsTransaction) {
        $this->pdo->beginTransaction();
      }

      // Concurrent calls for the same draft (final pick + admin regenerate) queue up here instead of both inserting
      $lock_stmt = $this->pdo->prepare("SELECT draft_id FROM draft WHERE draft_id = ? FOR UPDATE");
      $lock_stmt->bindParam(1, $draft->draft_id);
      if (!$lock_stmt->execute()) {
        throw new \Exception("Unable to lock draft for stats.");
      }

      $draft_stats = $this->_ReplaceDraftStats($draft);

      if ($ownsTransaction) {
        $this->pdo->commit();
        $this->drafts->FlushPendingCacheResets();
      }
    } catch (\Throwable $e) {
      if ($ownsTransaction) {
        if ($this->pdo->inTransaction()) {
          $this->pdo->rollBack();
        }
        $this->drafts->FlushPendingCacheResets();
      }

      throw $e;
    }

    return $draft_stats;
  }

  private function _ReplaceDraftStats(Draft $draft) {
    $draft_stats = new DraftStats();
    $draft_stats->draft_id = $draft->draft_id;
    $teams = $this->draftData->GetTeams($draft->draft_sport);
    $positions = $this->draftData->GetPositions($draft->draft_sport);

    $this->DeleteStats($draft->draft_id);

    $this->_LoadDraftSpecificStats($draft, $draft_stats);
    $this->_LoadManagerSuperlatives($draft->draft_id, $draft_stats);
    $this->_LoadAveragePickTime($draft->draft_id, $draft_stats);
    $this->_LoadRoundTimes($draft, $draft_stats);
    $this->_LoadTeamSuperlatives($draft->draft_id, $draft_stats, $teams);
    $this->_LoadPositionSuperlatives($draft->draft_id, $draft_stats, $positions);

    // Column names come from DraftStats' own properties, never from input
    $values = get_object_vars($draft_stats);
    unset($values['draft_stat_id']);
    $columns = array_keys($values);

    $insert_stmt = $this->pdo->prepare("INSERT INTO draft_stats (" . implode(', ', $columns) . ")
      VALUES (" . implode(', ', array_map(fn ($column) => ":$column", $columns)) . ")");

    foreach ($values as $column => $value) {
      $insert_stmt->bindValue(":$column", $value);
    }

    if (!$insert_stmt->execute()) {
      throw new \Exception("Unable to insert new draft stats row.");
    }

    $draft_stats->draft_stat_id = (int)$this->pdo->lastInsertId();

    $this->drafts->UpdateStatsTimestamp($draft);

    return $draft_stats;
  }

  public function DeleteStats($draft_id) {
    $delete_stmt = $this->pdo->prepare("DELETE FROM draft_stats WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if (!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete existing stats rows.");
    }
  }

  /** Runs a prepared statement and returns its first row (empty array when there is none). */
  private function _FetchRow(\PDOStatement $stmt) {
    if (!$stmt->execute()) {
      throw new \Exception("Unable to calculate draft stats.");
    }

    return $stmt->fetch() ?: array();
  }

  private function _LoadDraftSpecificStats(Draft $draft, DraftStats $stats) {
    $start_time = new \DateTime((string)$draft->draft_start_time, new \DateTimeZone("UTC"));
    $end_time = new \DateTime((string)$draft->draft_end_time, new \DateTimeZone("UTC"));

    $start_seconds = $start_time->getTimestamp();
    $end_seconds = $end_time->getTimestamp();

    $stats->drafting_time_seconds = (int)($end_seconds - $start_seconds);
  }

  /** One grouped query for every manager's average, slowest and fastest pick; the extremes are picked here. */
  private function _LoadManagerSuperlatives($draft_id, DraftStats $stats) {
    $stmt = $this->pdo->prepare("SELECT m.manager_name, avg(p.pick_duration) as pick_average,
      max(p.pick_duration) as pick_max, min(p.pick_duration) as pick_min
    FROM players p
    LEFT OUTER JOIN managers m
    ON m.manager_id = p.manager_id
    WHERE p.draft_id = ?
    AND p.pick_duration IS NOT NULL
    GROUP BY m.manager_name
    ORDER BY m.manager_name");

    $stmt->bindParam(1, $draft_id);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to calculate draft stats.");
    }

    $managers = $stmt->fetchAll();

    $row = $this->_Extreme($managers, 'pick_average', true);
    $stats->longest_avg_pick_manager_name = ($row['manager_name'] ?? null);
    $stats->longest_avg_pick_seconds = (int)($row['pick_average'] ?? null);

    $row = $this->_Extreme($managers, 'pick_average', false);
    $stats->shortest_avg_pick_manager_name = ($row['manager_name'] ?? null);
    $stats->shortest_avg_pick_seconds = (int)($row['pick_average'] ?? null);

    $row = $this->_Extreme($managers, 'pick_max', true);
    $stats->longest_single_pick_manager_name = ($row['manager_name'] ?? null);
    $stats->longest_single_pick_seconds = (int)($row['pick_max'] ?? null);

    $row = $this->_Extreme($managers, 'pick_min', false);
    $stats->shortest_single_pick_manager_name = ($row['manager_name'] ?? null);
    $stats->shortest_single_pick_seconds = (int)($row['pick_min'] ?? null);
  }

  /** The row with the highest ($highest) or lowest $column value; the first one wins a tie. Null when there are no rows. */
  private function _Extreme(array $rows, string $column, bool $highest) {
    $best = null;

    foreach ($rows as $row) {
      if ($best === null || ($highest ? (float)$row[$column] > (float)$best[$column] : (float)$row[$column] < (float)$best[$column])) {
        $best = $row;
      }
    }

    return $best;
  }

  private function _LoadAveragePickTime($draft_id, DraftStats $stats) {
    $stmt = $this->pdo->prepare("SELECT avg(p.pick_duration) as pick_average
    FROM players p
    WHERE p.draft_id = ?
    AND p.pick_duration IS NOT NULL
    LIMIT 1");

    $stmt->bindParam(1, $draft_id);

    $row = $this->_FetchRow($stmt);

    $stats->average_pick_seconds = (int)($row['pick_average'] ?? null);
  }

  private function _LoadRoundTimes(Draft $draft, DraftStats $stats) {
    $row = $this->_LoadRoundExtreme($draft->draft_id, 'DESC');
    $stats->longest_round = (int)($row['player_round'] ?? null);
    $stats->longest_round_seconds = (int)($row['round_time'] ?? null);

    $row = $this->_LoadRoundExtreme($draft->draft_id, 'ASC');
    $stats->shortest_round = (int)($row['player_round'] ?? null);
    $stats->shortest_round_seconds = (int)($row['round_time'] ?? null);

    $stmt = $this->pdo->prepare("SELECT sum( p.pick_duration ) / ? AS avg_round_time
    FROM players p
    WHERE p.draft_id = ?
    AND p.pick_duration IS NOT NULL");

    $stmt->bindValue(1, max(1, (int)$draft->draft_rounds), \PDO::PARAM_INT);
    $stmt->bindValue(2, $draft->draft_id);

    $row = $this->_FetchRow($stmt);

    $stats->average_round_seconds = (int)($row['avg_round_time'] ?? null);
  }

  /** The round with the most ($direction DESC) or least (ASC) total pick time. */
  private function _LoadRoundExtreme($draft_id, string $direction) {
    $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $this->pdo->prepare("SELECT p.player_round, sum( p.pick_duration ) AS round_time
    FROM players p
    WHERE p.draft_id = ?
    AND p.pick_duration IS NOT NULL
    GROUP BY player_round
    ORDER BY round_time $direction
    LIMIT 1");

    $stmt->bindParam(1, $draft_id);

    return $this->_FetchRow($stmt);
  }

  private function _LoadTeamSuperlatives($draft_id, DraftStats $stats, $teams) {
    $row = $this->_CountExtreme($draft_id, 'team', 'DESC');
    $stats->most_drafted_team = isset($row['team']) ? ($teams[$row['team']] ?? $row['team']) : "";
    $stats->most_drafted_team_count = (int)($row['occurrences'] ?? null);

    $row = $this->_CountExtreme($draft_id, 'team', 'ASC');
    $stats->least_drafted_team = isset($row['team']) ? ($teams[$row['team']] ?? $row['team']) : "";
    $stats->least_drafted_team_count = (int)($row['occurrences'] ?? null);
  }

  private function _LoadPositionSuperlatives($draft_id, DraftStats $stats, $positions) {
    $row = $this->_CountExtreme($draft_id, 'position', 'DESC');
    $stats->most_drafted_position = isset($row['position']) ? ($positions[$row['position']] ?? $row['position']) : "";
    $stats->most_drafted_position_count = (int)($row['occurrences'] ?? null);

    $row = $this->_CountExtreme($draft_id, 'position', 'ASC');
    $stats->least_drafted_position = isset($row['position']) ? ($positions[$row['position']] ?? $row['position']) : "";
    $stats->least_drafted_position_count = (int)($row['occurrences'] ?? null);
  }

  /** The most ($direction DESC) or least (ASC) drafted value of the players table's team or position column. */
  private function _CountExtreme($draft_id, string $column, string $direction) {
    $column = $column === 'position' ? 'position' : 'team';
    $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

    $stmt = $this->pdo->prepare("SELECT p.$column, count(p.$column) as occurrences
    FROM players p
    WHERE p.draft_id = ?
    AND p.$column IS NOT NULL
    GROUP BY p.$column
    ORDER BY occurrences $direction
    LIMIT 1");

    $stmt->bindParam(1, $draft_id);

    return $this->_FetchRow($stmt);
  }
}
