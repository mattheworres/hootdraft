<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\PickSearchModel;

class PickRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function Load($id) {
    $pick = new Pick();

    $pick_stmt = $this->app['db']->prepare("SELECT * FROM players WHERE player_id = ? LIMIT 1");
    $pick_stmt->bindParam(1, (int) $id);

    $pick_stmt->setFetchMode(\PDO::FETCH_INTO, $pick);

    if(!$pick_stmt->execute() || !$pick_stmt->fetch()) {
      throw new \Exception("Unable to load pick " . $id);
    }

    return $pick;
  }

  public function LoadAll($draft_id) {
    $picks = array();

    $picks_stmt = $this->app['db']->prepare("SELECT * FROM players WHERE draft_id = ? ORDER BY player_pick ASC");
    $picks_stmt->bindParam(1, $draft_id);

    $picks_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$picks_stmt->execute()) {
      throw new \Exception("Unable to load all picks for draft.");
    }

    while ($pick = $picks_stmt->fetch())
      $picks[] = $pick;

    return $picks;
  }

  public function LoadUpdatedPicks($draft_id, $pick_counter) {
    $picks = array();
    
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_counter > ? ORDER BY p.player_counter");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $pick_counter);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new \Exception("Unable to load updated picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }

  public function LoadLastPicks($draft_id, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.pick_time IS NOT NULL " .
            "AND p.pick_duration IS NOT NULL " .
            "ORDER BY p.player_pick DESC LIMIT ?");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $amount, \PDO::PARAM_INT);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load last $amount picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }

  public function LoadNextPicks($draft_id, $currentPick, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick > ? " .
            "ORDER BY p.player_pick ASC " .
            "LIMIT ?");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $currentPick);
    $stmt->bindParam(3, $amount, \PDO::PARAM_INT);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load next $amount picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }

  public function LoadManagerPicks($manager_id, $selected = true) {
    $manager_id = (int) $manager_id;

    if ($manager_id == 0) {
      throw new \Exception("Unable to get manager #" . $manager_id . "'s picks.");
    }

    $picks = array();

    $stmt = $selected
      ? $this->app['db']->prepare("SELECT * FROM players WHERE manager_id = ? AND pick_time IS NOT NULL ORDER BY player_pick ASC")
      : $this->app['db']->prepare("SELECT * FROM players WHERE manager_id = ? ORDER BY player_pick ASC");

    $stmt->bindParam(1, $manager_id);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load manager #$manager_id's picks.");
    }

    while ($pick = $stmt->fetch())
      $picks[] = $pick;

    return $picks;
  }

  public function LoadRoundPicks($draft_id, $draft_round, $sort_ascending = true, $selected = true) {
    $picks = array();
    $sortOrder = $sort_ascending ? "ASC" : "DESC";

    $stmt = $selected
      ? $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? AND p.pick_time IS NOT NULL ORDER BY p.player_pick " . $sortOrder)
      : $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? ORDER BY p.player_pick " . $sortOrder);

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $draft_round);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load round #$round's picks.");
    }

    while ($pick = $stmt->fetch())
      $picks[] = $pick;

    return $picks;
  }

  /**
   * Searches for picks with strict criteria, using the MATCH() and score method. Sorts by score ASC first, then pick DESC last.
   * @param int $draft_id 
   */
  public function SearchStrict(PickSearchModel $searchModel) {
    $draft_id = (int) $searchModel->draft_id;
    $param_number = 4;
    $players = array();

    $sql = "SELECT p.*, m.manager_name, MATCH (p.first_name, p.last_name) AGAINST (?) as search_score " .
            "FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE MATCH (p.first_name, p.last_name) AGAINST (?) AND p.draft_id = ? ";

    if ($searchModel->hasTeam())
      $sql .= "AND p.team = ? ";

    if ($searchModel->hasPosition())
      $sql .= "AND p.position = ? ";

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY search_score ASC, p.player_pick DESC";

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

    while ($player = $stmt->fetch())
      $players[] = $player;

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * Search picks by a loose criteria that uses a LIKE %% query. Used if strict query returns 0 results. Sorts by pick DESC.
   * @param int $draft_id 
   */
  public function SearchLoose(PickSearchModel $searchModel) {
    $draft_id = (int) $searchModel->draft_id;
    $players = array();
    $param_number = 2;
    $loose_search_score = -1;

    $sql = "SELECT p.*, m.manager_name FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ? ";

    if ($searchModel->hasName())
      $sql .= "AND (p.first_name LIKE ? OR p.last_name LIKE ?)";

    if ($searchModel->hasTeam())
      $sql .= "AND p.team = ? ";

    if ($searchModel->hasPosition())
      $sql .= "AND p.position = ? ";

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick DESC";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $draft_id);

    if ($searchModel->hasName()) {
      $stmt->bindParam($param_number, $keywords);
      $param_number++;
      $stmt->bindParam($param_number, $keywords);
      $param_number++;

      $keywords = "%" . $searchModel->keywords . "%";
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
      $players[] = $player;

      $loose_search_score--;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }
}