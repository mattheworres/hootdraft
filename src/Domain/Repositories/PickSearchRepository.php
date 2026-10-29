<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\PickSearchModel;

class PickSearchRepository {
  private const TIER_FULL_NAME = 0;
  private const TIER_EQUALS = 1;
  private const TIER_STARTS_WITH = 2;
  private const TIER_CONTAINS = 3;
  /** search_score is MAX_TIER minus the tier, so a better match has a higher score */
  private const MAX_TIER = self::TIER_CONTAINS;

  public function __construct(private \PDO $pdo) {
  }

  /**
   * Searches the Selected Picks of a draft. Every word of the keywords must be in the first or the last name.
   * Ranks the picks by how well the name matches (see nameTier), then by pick number in the requested direction.
   */
  public function Search(PickSearchModel $searchModel) {
    $draft_id = (int)$searchModel->draft_id;
    $words = $searchModel->hasName() ? preg_split('/\s+/', trim($searchModel->keywords), -1, PREG_SPLIT_NO_EMPTY) : [];
    $params = [$draft_id];

    $sql = "SELECT p.*, m.manager_name FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ? ";

    foreach ($words as $word) {
      $sql .= "AND (p.first_name LIKE ? OR p.last_name LIKE ?) ";
      $like = "%" . addcslashes($word, "\\%_") . "%";
      $params[] = $like;
      $params[] = $like;
    }

    if ($searchModel->hasTeam()) {
      $sql .= "AND p.team = ? ";
      $params[] = $searchModel->team;
    }

    if ($searchModel->hasPosition()) {
      $sql .= "AND p.position = ? ";
      $params[] = $searchModel->position;
    }

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick $searchModel->sort";

    $stmt = $this->pdo->prepare($sql);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, Pick::class);

    if (!$stmt->execute($params)) {
      throw new \Exception("Unable to search for picks.");
    }

    $players = array();

    while ($player = $stmt->fetch()) {
      $player->search_score = self::MAX_TIER - $this->nameTier($player, $words);
      $player->selected = strlen((string) $player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;
    }

    // The sort is stable, so the SQL pick order stays within one score
    usort($players, fn($a, $b) => $b->search_score <=> $a->search_score);

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * 0: the words are the full name, in either order. Otherwise the worst word decides:
   * 1: equals a name. 2: starts a name. 3: is inside a name.
   */
  private function nameTier(Pick $player, array $words): int {
    if (count($words) == 0) {
      return self::MAX_TIER;
    }

    $first = mb_strtolower((string) $player->first_name);
    $last = mb_strtolower((string) $player->last_name);
    $lowered = array_map('mb_strtolower', $words);

    if (count($lowered) == 2 && (($lowered[0] == $first && $lowered[1] == $last) || ($lowered[0] == $last && $lowered[1] == $first))) {
      return self::TIER_FULL_NAME;
    }

    $tier = self::TIER_EQUALS;

    foreach ($lowered as $word) {
      $wordTier = self::TIER_CONTAINS;

      foreach ([$first, $last] as $name) {
        if ($name == $word) {
          $wordTier = min($wordTier, self::TIER_EQUALS);
        } elseif (str_starts_with($name, $word)) {
          $wordTier = min($wordTier, self::TIER_STARTS_WITH);
        }
      }

      $tier = max($tier, $wordTier);
    }

    return $tier;
  }

  //Analogous to 1.3's "getAlreadyDrafted" method from the player service - used on the add pre-check
  public function SearchAlreadyDrafted($draft_id, $first_name, $last_name) {
    $picks = array();

    $stmt = $this->pdo->prepare("SELECT p.*, m.manager_name " .
      "FROM players p " .
      "LEFT OUTER JOIN managers m " .
      "ON m.manager_id = p.manager_id " .
      "WHERE p.draft_id = ? " .
      "AND p.pick_time IS NOT NULL " .
      "AND p.first_name = ? " .
      "AND p.last_name = ? " .
      "ORDER BY p.player_pick");

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $first_name);
    $stmt->bindParam(3, $last_name);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, Pick::class);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to check to see if $first_name $last_name was already drafted.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen((string) $pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }

    return $picks;
  }
}
