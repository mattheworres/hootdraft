<?php

/**
 * Represents a PHPDraft player, or "pick" in the draft.
 * 
 * Each player is owned by a manager, who belongs to a draft.
 * 
 * Players carry draft information on them - such as which round and which pick
 * they exist at (player information will be blank if they are unchecked)
 */
class player_service {

  public function loadPlayer($id = 0) {
    $id = (int) $id;

    $player = new player_object();

    if ($id == 0) {
      return $player;
    }

    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT * FROM players WHERE player_id = ? LIMIT 1");
    $stmt->bindParam(1, $id);

    $stmt->setFetchMode(PDO::FETCH_INTO, $player);

    if (!$stmt->execute()) {
      throw new Exception("Unable to load player.");
    }

    if (!$stmt->fetch()) {
      throw new Exception("Unable to load player.");
    }

    return $player;
  }

  /**
   * Saves or updates a player. NOTE: Does not update or save the player's pick time or pick duration. Those must be handled separately.
   * @return player_object $player on success, exception thrown otherwise
   */
  public function savePlayer(player_object $player, $setPickToNow = false) {
    global $DBH; /* @var $DBH PDO */
    if ($player->player_id > 0) {
      //NOTE: I don't care for this... But I had to use a param counter to get around the statement needing to be dynamic. There are instances
      //like when an already-made pick doesn't need the pick_time updated. Always up for a more elegant solution.

      $param_number = 9;

      $sql = "UPDATE players SET manager_id = ?, draft_id = ?, first_name = ?, last_name = ?, team = ?, position = ?, player_round = ?, player_pick = ? ";

      if ($setPickToNow === true) {
        $player->pick_time = php_draft_library::getNowPhpTime();
        $sql .= ", pick_time = ? ";
      }

      $sql .= "WHERE player_id = ?";

      $stmt = $DBH->prepare($sql);
      $stmt->bindParam(1, $player->manager_id);
      $stmt->bindParam(2, $player->draft_id);
      $stmt->bindParam(3, $player->first_name);
      $stmt->bindParam(4, $player->last_name);
      $stmt->bindParam(5, $player->team);
      $stmt->bindParam(6, $player->position);
      $stmt->bindParam(7, $player->player_round);
      $stmt->bindParam(8, $player->player_pick);

      if ($setPickToNow === true) {
        $stmt->bindParam($param_number, $player->pick_time);
        $param_number++;
      }

      $stmt->bindParam($param_number, $player->player_id);

      if (!$stmt->execute()) {
        throw new Exception("Unable to save player.");
      }

      return $player;
    } elseif ($player->draft_id > 0 && $player->manager_id > 0) {
      $stmt = $DBH->prepare("INSERT INTO players (manager_id, draft_id, player_round, player_pick) VALUES (?, ?, ?, ?)");
      $stmt->bindParam(1, $player->manager_id);
      $stmt->bindParam(2, $player->draft_id);
      $stmt->bindParam(3, $player->player_round);
      $stmt->bindParam(4, $player->player_pick);

      if (!$stmt->execute()) {
        throw new Exception("Unable to save player.");
      }

      $player->player_id = (int) $DBH->lastInsertId();

      return $player;
    }
    else
      throw new Exception("Unable to save player.");
  }

  /**
   * Get the validity of this object as it stands to ensure it can be updated as a pick
   * @param draft_object $draft The draft this pick is being submitted for
   * @return array $errors Array of string error messages 
   */
  public function getValidity(draft_object $draft, player_object $player) {
    $errors = array();

    if (empty($player->draft_id) || $player->draft_id == 0)
      $errors[] = "Player doesn't belong to a draft.";
    if (empty($player->manager_id) || $player->manager_id == 0)
      $errors[] = "Player doesn't belong to a manager.";
    if (empty($player->player_id) || $player->player_id == 0)
      $errors[] = "Player doesn't have an ID.";

    if (!$this->pickExists($player))
      $errors[] = "Player doesn't exist.";

    if (!isset($player->first_name) || strlen($player->first_name) == 0)
      $errors[] = "Player must have a first name.";
    if (!isset($player->last_name) || strlen($player->last_name) == 0)
      $errors[] = "Player must have a last name.";
    if (!isset($player->team) || strlen($player->team) == 0 || strlen($draft->sports_teams[$player->team]) == 0)
      $errors[] = "Player has an invalid team.";
    if (!isset($player->position) || strlen($player->position) == 0 || strlen($draft->sports_positions[$player->position]) == 0)
      $errors[] = "Player has an invalid position.";

    return $errors;
  }

  public function updatePickDuration(player_object $player, player_object $previous_pick, draft_object $draft) {
    global $DBH; /* @var $DBH PDO */

    if (!isset($player->pick_time) || strlen($player->pick_time) == 0)
      throw new Exception("Must call updatePickDuration on a player object that already has its own pick_time set!");

    if ($player->player_pick == 1 || $previous_pick === false)
      $start_time = strtotime($draft->draft_start_time);
    else
      $start_time = strtotime($previous_pick->pick_time);

    $now = strtotime($player->pick_time);

    $alloted_time = $now - $start_time;

    $player->pick_duration = (int) $alloted_time;

    $stmt = $DBH->prepare("UPDATE players SET pick_duration = ? WHERE player_id = ?");
    $stmt->bindParam(1, $alloted_time);
    $stmt->bindParam(2, $player->player_id);

    return $stmt->execute();
  }

  /**
   * For a draft get the ten most recent picks that have occurred.
   * @param int $draft_id
   * @return array|boolean Array of picks, or false on error.
   */
  public function getLastTenPicks($draft_id) {
    global $DBH; /* @var $DBH PDO */
    $draft_id = (int) $draft_id;
    $picks = array();

    if ($draft_id == 0) {
      throw new Exception("Draft is invalid");
    }

    $stmt = $DBH->prepare("SELECT p.*, m.manager_name, m.manager_id " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.pick_time IS NOT NULL " .
            "AND p.pick_duration IS NOT NULL " .
            "ORDER BY p.player_pick DESC LIMIT 10");

    $stmt->bindParam(1, $draft_id);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get last ten picks.");
    }

    while ($player = $stmt->fetch())
      $picks[] = $player;

    return $picks;
  }

  /**
   * Grab the last five completed draft picks
   * @param draft_object $draft
   * @return array last five picks 
   */
  public function getLastFivePicks(draft_object $draft) {
    global $DBH; /* @var $DBH PDO */
    $picks = array();

    $stmt = $DBH->prepare("SELECT p.*, m.* " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.pick_time IS NOT NULL " .
            "ORDER BY p.player_pick DESC " .
            "LIMIT 5");

    $stmt->bindParam(1, $draft->draft_id);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get last five picks.");
    }

    while ($player = $stmt->fetch())
      $picks[] = $player;

    return $picks;
  }

  /**
   * Get the previous (completed) pick in the draft
   * @param draft_object $draft
   * @return player_object $last_player, or false on 0 rows
   */
  public function getLastPick(draft_object $draft) {
    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT p.*, m.* " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? " .
            "AND p.pick_time IS NOT NULL " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $current_pick);

    $current_pick = ($draft->draft_current_pick - 1);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get last pick.");
    }

    if ($stmt->rowCount() == 0) {
      throw new Exception("Unable to get last pick.");
    }

    return $stmt->fetch();
  }

  /**
   * Called from a draft or statically from a presenter, gets the current pick "on the clock"
   * @param draft_object $draft Object to get the current pick for
   * @return player_object The current pick
   */
  public function getCurrentPick(draft_object $draft) {
    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT p.*, m.* " .
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

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get current pick.");
    }

    if ($stmt->rowCount() == 0) {
      throw new Exception("Unable to get current pick.");
    }

    return $stmt->fetch();
  }

  /**
   * Get the next pick object
   * @param draft_object $draft
   * @return player_object the next pick 
   */
  public function getNextPick(draft_object $draft) {
    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT p.*, m.* " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $current_pick);

    $current_pick = $draft->draft_current_pick + 1;

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get next pick.");
    }

    if ($stmt->rowCount() == 0) {
      throw new Exception("Unable to get next pick.");
    }

    return $stmt->fetch();
  }

  /**
   * Get the next five picks
   * @param draft_object $draft
   * @return array of player_objects 
   */
  public function getNextFivePicks(draft_object $draft) {
    global $DBH; /* @var $DBH PDO */
    $picks = array();

    $stmt = $DBH->prepare("SELECT p.*, m.* " .
            "FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick > ? " .
            "ORDER BY p.player_pick ASC " .
            "LIMIT 5");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft->draft_current_pick);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to load next five picks.");
    }

    while ($player = $stmt->fetch())
      $picks[] = $player;

    return $picks;
  }

  /**
   * Get all players/picks for a given draft.
   * @param int $draft_id ID of the draft to get players for
   * @return array Player objects that belong to given draft. false on failure
   */
  public function getPlayersByDraft($draft_id) {
    global $DBH; /* @var $DBH PDO */
    $draft_id = (int) $draft_id;

    if ($draft_id == 0)
      return false;

    $players_stmt = $DBH->prepare("SELECT * FROM players WHERE draft_id = ? ORDER BY player_pick ASC");
    $players_stmt->bindParam(1, $draft_id);

    $players_stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    $players = array();

    if (!$players_stmt->execute()) {
      throw new Exception("Unable to get players by draft.");
    }

    while ($player = $players_stmt->fetch())
      $players[] = $player;

    return $players;
  }

  public function deletePlayersByDraft($draft_id) {
    global $DBH; /* @var $DBH PDO */
    $draft_id = (int) $draft_id;

    if ($draft_id == 0) {
      throw new Exception("Unable to delete players for this draft - invalid draft ID.");
    }

    $players = $this->getPlayersByDraft($draft_id);

    if (count($players) == 0) {
      return;
    }

    $id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.

    foreach ($players as $player) {
      $id_string .= "," . (int) $player->player_id;
    }

    if (!$DBH->exec("DELETE FROM players WHERE player_id IN (" . $id_string . ")")) {
      throw new Exception("Unable to delete players for this draft.");
    }
  }

  /**
   * Check to ensure the pick exists in the database
   * @return boolean result (if pick exists)
   */
  public function pickExists(player_object $player) {
    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT player_id FROM players WHERE player_id = ? AND draft_id = ? AND player_pick = ? AND player_round = ? LIMIT 1");
    $stmt->bindParam(1, $player->player_id);
    $stmt->bindParam(2, $player->draft_id);
    $stmt->bindParam(3, $player->player_pick);
    $stmt->bindParam(4, $player->player_round);

    if (!$stmt->execute()) {
      throw new Exception("Unable to check if pick exists.");
    }

    //Note: boolean result here means something
    return $stmt->rowCount() == 1;
  }

  /**
   * Get all players/picks for a given manager that have been selected.
   * @param int $manager_id ID of the manager to get players for
   * @return array Player objects that belong to given manager. false on failure
   */
  public function getSelectedPlayersByManager($manager_id) {
    global $DBH; /* @var $DBH PDO */
    $manager_id = (int) $manager_id;

    if ($manager_id == 0) {
      throw new Exception("Unable to get manager #" . $manager_id . "'s players.");
    }

    $players = array();

    $stmt = $DBH->prepare("SELECT * FROM players WHERE manager_id = ? AND pick_time IS NOT NULL ORDER BY player_pick ASC");
    $stmt->bindParam(1, $manager_id);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get manager #" . $manager_id . "'s players.");
    }

    while ($player = $stmt->fetch())
      $players[] = $player;

    return $players;
  }

  /**
   * Get all players/picks for a given manager, regardless of selection
   * @param int $manager_id 
   * @return array Player objects that belong to a given manager, or false on failure.
   */
  public function getAllPlayersByManager($manager_id, $sort_by_pick = false) {
    global $DBH; /* @var $DBH PDO */
    $manager_id = (int) $manager_id;
    $players = array();

    if ($manager_id == 0) {
      throw new Exception("Unable to get manager #" . $manager_id . "'s players.");
    }

    $sql = "SELECT * FROM players WHERE manager_id = ?";

    if ($sort_by_pick)
      $sql .= " ORDER BY player_pick";

    $stmt = $DBH->prepare($sql);
    $stmt->bindParam(1, $manager_id);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get manager #" . $manager_id . "'s players.");
    }

    while ($player = $stmt->fetch())
      $players[] = $player;

    return $players;
  }

  /**
   * Get all selected players for a given round.
   * @param int $draft_id ID of the draft for the given round
   * @param int $round Round to get players for
   * @param bool $sort_ascending Whether to sort by ASC or not. False == DESC
   * @return array Player objects that belong in a given round. false on failure
   */
  public function getSelectedPlayersByRound($draft_id, $round, $sort_ascending = true) {
    global $DBH; /* @var $DBH PDO */
    $players = array();
    $sortOrder = $sort_ascending ? "ASC" : "DESC";

    $draft_id = (int) $draft_id;
    $round = (int) $round;

    if ($draft_id == 0 || $round == 0) {
      throw new Exception("Unable to get round #" . $round . "'s players.");
    }

    $stmt = $DBH->prepare("SELECT p.*, m.manager_name FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? AND p.pick_time IS NOT NULL ORDER BY p.player_pick " . $sortOrder);

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $round);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get round #" . $round . "'s players.");
    }

    while ($player = $stmt->fetch())
      $players[] = $player;

    return $players;
  }

  /**
   * Get all picks, selected or not, for a given round. Use $sort if your style is serpentine.
   * @param int $draft_id ID of the draft for the given round
   * @param int $round Round to get players for
   * @param bool $sort Whether to sort by ASC or not. False == DESC
   * @return array Player objects that belong in a given round. false on failure
   */
  public function getAllPlayersByRound($draft_id, $round, $sort = true) {
    global $DBH; /* @var $DBH PDO */
    $players = array();
    $sortOrder = $sort ? "ASC" : "DESC";

    $draft_id = (int) $draft_id;
    $round = (int) $round;

    if ($draft_id == 0 || $round == 0) {
      throw new Exception("Unable to get round #" . $round . "'s players.");
    }

    $stmt = $DBH->prepare("SELECT p.*, m.manager_name FROM players p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? ORDER BY p.player_pick " . $sortOrder);

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $round);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get round #" . $round . "'s players.");
    }

    if ($stmt->rowCount() == 0) {
      throw new Exception("Unable to get round #" . $round . "'s players.");
    }

    while ($player = $stmt->fetch())
      $players[] = $player;

    return $players;
  }

}

?>
