<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PickSearchModel;

class PickRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //Used for when a pick is entered (made)
  public function AddPick(Pick $pick) {
    $add_stmt = $this->app['db']->prepare("UPDATE players
      SET first_name = ?, last_name = ?, team = ?, position = ?, player_counter = ?, pick_time = ?, pick_duration = ?
      WHERE player_id = ?");

    $add_stmt->bindParam(1, $pick->first_name);
    $add_stmt->bindParam(2, $pick->last_name);
    $add_stmt->bindParam(3, $pick->team);
    $add_stmt->bindParam(4, $pick->position);
    $add_stmt->bindParam(5, $pick->player_counter);
    $add_stmt->bindParam(6, $pick->pick_time);
    $add_stmt->bindParam(7, $pick->pick_duration);
    $add_stmt->bindParam(8, $pick->player_id);

    if (!$add_stmt->execute()) {
      throw new Exception("Unable to save pick #$pick->player_pick.");
    }

    return $pick;
  }

  public function Load($id) {
    $pick = new Pick();

    $pick_stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM players p
      LEFT OUTER JOIN managers m ON p.manager_id = m.manager_id
      WHERE player_id = ? LIMIT 1");
    $pick_stmt->bindParam(1, $id);

    $pick_stmt->setFetchMode(\PDO::FETCH_INTO, $pick);

    if (!$pick_stmt->execute() || !$pick_stmt->fetch()) {
      throw new \Exception("Unable to load pick " . $id);
    }

    $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;

    return $pick;
  }

  //Is used by public draft board, need to consider serpentine or standard ordering
  public function LoadAll(Draft $draft) {
    $picks = array();

    $sort = true;
    for ($i = 1; $i <= $draft->draft_rounds; ++$i) {
      if ($draft->draft_style == "serpentine") {
        $picks[] = $this->LoadRoundPicks($draft, $i, $sort, false);
        $sort = $sort ? false : true;
      } else {
        $picks[] = $this->LoadRoundPicks($draft, $i, true, false);
      }
    }

    return $picks;
  }

  public function LoadUpdatedPicks($draft_id, $pick_counter) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_counter > ? ORDER BY p.player_counter");

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $pick_counter);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load updated picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }

    return $picks;
  }

  public function UpdatePick(Pick $pick) {
    $update_stmt = $this->app['db']->prepare("UPDATE players SET manager_id = ?, first_name = ?, last_name = ?, team = ?, position = ?,
      pick_time = ?, pick_duration = ?, player_counter = ? WHERE player_id = ?");

    $update_stmt->bindParam(1, $pick->manager_id);
    $update_stmt->bindParam(2, $pick->first_name);
    $update_stmt->bindParam(3, $pick->last_name);
    $update_stmt->bindParam(4, $pick->team);
    $update_stmt->bindParam(5, $pick->position);
    $update_stmt->bindParam(6, $pick->pick_time);
    $update_stmt->bindParam(7, $pick->pick_duration);
    $update_stmt->bindParam(8, $pick->player_counter);
    $update_stmt->bindParam(9, $pick->player_id);

    if (!$update_stmt->execute()) {
      throw new \Exception("Unable to update pick #$pick->player_id");
    }

    return $pick;
  }

  //Used for when a pick has been updated on the depth chart (public-ish)
  public function UpdatePickDepthChart(Pick $pick) {
    $update_stmt = $this->app['db']->prepare("UPDATE players SET depth_chart_position_id = ?, position_eligibility = ? WHERE player_id = ?");

    $update_stmt->bindParam(1, $pick->depth_chart_position_id);
    $update_stmt->bindParam(2, $pick->position_eligibility);
    $update_stmt->bindParam(3, $pick->player_id);

    if (!$update_stmt->execute()) {
      throw new \Exception("Unable to update pick #$pick->player_id for depth chart.");
    }

    return $pick;
  }

  public function GetCurrentPick(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_round = ? " .
            "AND p.player_pick = ? " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft->draft_current_round);
    $stmt->bindParam(3, $draft->draft_current_pick);

    //Saw some extra numbered properties in the object when a FETCH_CLASS was performed instead. Possibly from the JOIN? Use FETCH_INTO instead:
    $current_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $current_pick);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get current pick.");
    }

    if ($stmt->rowCount() == 0) {
      throw new \Exception("Unable to get current pick.");
    }

    $stmt->fetch();

    $current_pick->selected = strlen($current_pick->pick_time) > 0 && $current_pick->pick_duration > 0;
    $current_pick->on_the_clock = true;

    return $current_pick;
  }

  public function GetPreviousPick(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? " .
            "AND p.pick_time IS NOT NULL " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $previous_pick_number);

    $previous_pick_number = ($draft->draft_current_pick - 1);

    $previous_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $previous_pick);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get last pick: " . implode(":", $stmt->errorInfo()));
    }

    if ($stmt->rowCount() == 0) {
      return null;
    }

    $stmt->fetch();

    $previous_pick->selected = strlen($previous_pick->pick_time) > 0 && $previous_pick->pick_duration > 0;
    $previous_pick->on_the_clock = false;

    return $previous_pick;
  }

  public function GetNextPick(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $current_pick_number);

    $current_pick_number = $draft->draft_current_pick + 1;

    $next_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $next_pick);

    if (!$stmt->execute()) {
      throw new Exception("Unable to get next pick.");
    }

    if ($stmt->rowCount() == 0) {
      return null;
    }

    $stmt->fetch();

    $next_pick->on_the_clock = true;

    return $next_pick;
  }

  public function LoadLastPicks($draft_id, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.pick_time IS NOT NULL " .
            "AND p.pick_duration IS NOT NULL " .
            "ORDER BY p.player_pick DESC " .
            "LIMIT ?");

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $amount, \PDO::PARAM_INT);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new Exception("Unable to load last $amount picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }

    return $picks;
  }

  public function LoadNextPicks($draft_id, $currentPick, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick >= ? " .
            "ORDER BY p.player_pick ASC " .
            "LIMIT ?");

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $currentPick);
    $stmt->bindParam(3, $amount, \PDO::PARAM_INT);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new Exception("Unable to load next $amount picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $pick->player_pick == $currentPick;

      $picks[] = $pick;
    }

    return $picks;
  }

  public function LoadManagerPicks($manager_id, $draft = null, $selected = true) {
    $manager_id = (int)$manager_id;

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

    while ($pick = $stmt->fetch()) {
      $pick->player_pick = (int)$pick->player_pick;
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $draft != null && $pick->player_pick == $draft->draft_current_pick;

      $picks[] = $pick;
    }

    return $picks;
  }

  public function LoadRoundPicks(Draft $draft, $draft_round, $sort_ascending = true, $selected = true) {
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

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft_round);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load round #$round's picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $draft != null && $pick->player_pick == $draft->draft_current_pick;

      $picks[] = $pick;
    }

    return $picks;
  }

  public function DeleteAllPicks($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM players WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if (!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete picks for $draft_id.");
    }

    return;
  }

  /*This logic will create all pick objects according to the draft information.
    The two lists are used in alternation for serpentine drafts. Only first list is
    used for standard drafts.*/
  public function SetupPicks(Draft $draft, $ascending_managers, $descending_managers = null) {
    $pick = 1;
    $even = true;

    for ($current_round = 1; $current_round <= $draft->draft_rounds; $current_round++) {
      if ($draft->draft_style == "serpentine") {
        if ($even) {
          $managers = $ascending_managers;
          $even = false;
        } else {
          if ($descending_managers == null) {
            throw new \Exception("Descending managers list is empty - unable to setup draft.");
          }

          $managers = $descending_managers;
          $even = true;
        }
      } else {
        $managers = $ascending_managers;
      }

      foreach ($managers as $manager) {
        $new_pick = new Pick();
        $new_pick->manager_id = $manager->manager_id;
        $new_pick->draft_id = $draft->draft_id;
        $new_pick->player_round = $current_round;
        $new_pick->player_pick = $pick;

        try {
          $this->_saveSetupPick($new_pick);
        } catch (Exception $e) {
          throw new Exception($e->getMessage());
        }

        $pick++;
      }
    }
    return;
  }

  //Used when SetupPicks is called, which is when a draft is flipped to "in_progress"
  private function _saveSetupPick(Pick $pick) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO players
      (manager_id, draft_id, player_round, player_pick)
      VALUES
      (?, ?, ?, ?)");

    $insert_stmt->bindParam(1, $pick->manager_id);
    $insert_stmt->bindParam(2, $pick->draft_id);
    $insert_stmt->bindParam(3, $pick->player_round);
    $insert_stmt->bindParam(4, $pick->player_pick);

    if (!$insert_stmt->execute()) {
      throw new \Exception("Unable to insert pick #$pick->player_pick for draft $pick->draft_id");
    }

    return;
  }
}
