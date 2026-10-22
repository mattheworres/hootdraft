<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\PickSearchModel;

class PickSearchRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  /**
   * Searches for picks with strict criteria, using the MATCH() and score method. Sorts by score ASC first, then pick DESC last.
   * @param int $draft_id
   */
  public function SearchStrict(PickSearchModel $searchModel) {
    $draft_id = (int)$searchModel->draft_id;
    $param_number = 4;
    $players = array();

    $sql = "SELECT p.*, m.manager_name, MATCH (p.first_name, p.last_name) AGAINST (?) as search_score " .
            "FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE MATCH (p.first_name, p.last_name) AGAINST (?) AND p.draft_id = ? ";

    if ($searchModel->hasTeam()) {
      $sql .= "AND p.team = ? ";
    }

    if ($searchModel->hasPosition()) {
      $sql .= "AND p.position = ? ";
    }

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY search_score ASC, p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $searchModel->keywords);
    $stmt->bindParam(2, $searchModel->keywords);
    $stmt->bindParam(3, $draft_id);

    if ($searchModel->hasTeam()) {
      $stmt->bindParam(4, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * Search picks by a loose criteria that uses a LIKE %% query. Used if strict query returns 0 results. Sorts by pick DESC.
   * @param int $draft_id
   */
  public function SearchLoose(PickSearchModel $searchModel) {
    $draft_id = (int)$searchModel->draft_id;
    $players = array();
    $param_number = 2;
    $loose_search_score = -1;

    $sql = "SELECT p.*, m.manager_name FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ? ";

    if ($searchModel->hasName()) {
      $sql .= "AND (p.first_name LIKE ? OR p.last_name LIKE ?)";
    }

    if ($searchModel->hasTeam()) {
      $sql .= "AND p.team = ? ";
    }

    if ($searchModel->hasPosition()) {
      $sql .= "AND p.position = ? ";
    }

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $draft_id);

    if ($searchModel->hasName()) {
      $keywords = "%" . $searchModel->keywords . "%";

      $stmt->bindParam($param_number, $keywords);
      $param_number++;
      $stmt->bindParam($param_number, $keywords);
      $param_number++;
    }

    if ($searchModel->hasTeam()) {
      $stmt->bindParam($param_number, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->search_score = $loose_search_score;
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;

      $loose_search_score--;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * Search picks by assuming a first + last combo was entered. Used if strict and loose queries return 0 and theres a space in the name. Sorts by pick DESC.
   * @param int $draft_id
   */
  public function SearchSplit(PickSearchModel $searchModel, $first_name, $last_name) {
    $draft_id = (int)$searchModel->draft_id;
    $players = array();
    $param_number = 4;
    $loose_search_score = -1;

    $sql = "SELECT p.*, m.manager_name FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ?
      AND (p.first_name LIKE ? OR p.last_name LIKE ?)
      AND p.pick_time IS NOT NULL ORDER BY p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $first_name);
    $stmt->bindParam(3, $last_name);

    if ($searchModel->hasTeam()) {
      $stmt->bindParam($param_number, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->search_score = $loose_search_score;
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;

      $loose_search_score--;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  //Analogous to 1.3's "getAlreadyDrafted" method from the player service - used on the add pre-check
  public function SearchAlreadyDrafted($draft_id, $first_name, $last_name) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name " .
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

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to check to see if $first_name $last_name was already drafted.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }

    return $picks;
  }
}
