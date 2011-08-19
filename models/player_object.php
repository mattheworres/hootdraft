<?php

require_once("models/search_object.php");
/**
 * Represents a PHPDraft player, or "pick" in the draft.
 * 
 * Each player is owned by a manager, who belongs to a draft.
 * 
 * Players carry draft information on them - such as which round and which pick
 * they exist at (player information will be blank if they are unchecked)
 * 
 * @property int $player_id The unique ID for this player
 * @property int $manager_id The ID of the manager this player belongs to
 * @property int $draft_id The ID of the draft this player belongs to
 * @property string $first_name The first name of the player
 * @property string $last_name The last name of the player
 * @property string $team The professional team the player plays for. Stored as three character abbreviation
 * @property string $position The position the player plays. Stored as one or three character abbreviation.
 * @property string $pick_time Timestamp of when the player was picked. Use strtotime to compare to other dates.
 * @property int $pick_duration Amount of seconds that were consumed during this pick
 * @property int $player_round Round the player was selected in
 * @property int $player_pick Pick the player was selected at
 * @property string $manager_name Name of the manager that made the pick. NOTE: Only available on selected picks, and is kind've a cheat.
 */
class player_object {

	public $player_id;
	public $manager_id;
	public $draft_id;
	public $first_name;
	public $last_name;
	public $team;
	public $position;
	public $pick_time;
	public $pick_duration;
	public $player_round;
	public $player_pick;
	public $manager_name;
	public $search_score;
	
	// <editor-fold defaultstate="collapsed" desc="Dynamic Properties">
	/**
	 * Returns a properly formatted player name in this format: "Last, First"
	 * @return string Player's proper name. 
	 */
	public function properName() {
		return $this->last_name . ", " . $this->first_name;
	}
	
	/**
	 * Returns a casually formatted player name in this format: "First Last"
	 * @return string Player's casual name 
	 */
	public function casualName() {
		return $this->first_name . " " . $this->last_name;
	}
	// </editor-fold>

	public function __construct($id = 0) {
		$id = (int)$id;
		
		if($id == 0)
			return false;
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM players WHERE player_id = ? LIMIT 1");
		$stmt->bindParam(1, $id);
		
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return false;
		
		if(!$stmt->fetch())
			return false;

		return true;
	}

	/**
	 * Saves or updates a player. NOTE: Does not update or save the player's pick time or pick duration. Those must be handled separately.
	 * @return bool true on success, false otherwise 
	 */
	public function savePlayer($setPickToNow = false) {
		global $DBH; /* @var $DBH PDO */
		if($this->player_id > 0) {
			//NOTE: I don't care for this... But I had to use a param counter to get around the statement needing to be dynamic. There are instances
			//like when an already-made pick doesn't need the pick_time updated. Always up for a more elegant solution.
			
			$param_number = 9;
			
			$sql = "UPDATE players SET manager_id = ?, draft_id = ?, first_name = ?, last_name = ?, team = ?, position = ?, player_round = ?, player_pick = ? ";
			if($setPickToNow === true) {
				$this->pick_time = php_draft_library::getNowPhpTime();
				$sql .= ", pick_time = ? ";
			}
			$sql .= "WHERE player_id = ?";
			
			$stmt = $DBH->prepare($sql);
			$stmt->bindParam(1, $this->manager_id);
			$stmt->bindParam(2, $this->draft_id);
			$stmt->bindParam(3, $this->first_name);
			$stmt->bindParam(4, $this->last_name);
			$stmt->bindParam(5, $this->team);
			$stmt->bindParam(6, $this->position);
			$stmt->bindParam(7, $this->player_round);
			$stmt->bindParam(8, $this->player_pick);
			if($setPickToNow === true) {
				$stmt->bindParam($param_number, $this->pick_time);
				$param_number++;
			}
			$stmt->bindParam($param_number, $this->player_id);
			
			$success = $stmt->execute();
			
			return $success;
		} elseif($this->draft_id > 0 && $this->manager_id > 0) {
			$stmt = $DBH->prepare("INSERT INTO players (manager_id, draft_id, player_round, player_pick) VALUES (?, ?, ?, ?)");
			$stmt->bindParam(1, $this->manager_id);
			$stmt->bindParam(2, $this->draft_id);
			$stmt->bindParam(3, $this->player_round);
			$stmt->bindParam(4, $this->player_pick);
			
			if(!$stmt->execute())
				return false;

			$this->player_id = (int)$DBH->lastInsertId();

			return true;
		}else
			return false;
	}
	
	/**
	 * Get the validity of this object as it stands to ensure it can be updated as a pick
	 * @param draft_object $draft The draft this pick is being submitted for
	 * @return array $errors Array of string error messages 
	 */
	public function getValidity(draft_object $draft) {
		$errors = array();
		
		if(empty($this->draft_id) || $this->draft_id == 0)
			$errors[] = "Player doesn't belong to a draft.";
		if(empty($this->manager_id) || $this->manager_id == 0)
			$errors[] = "Player doesn't belong to a manager.";
		if(empty($this->player_id) || $this->player_id == 0)
			$errors[] = "Player doesn't have an ID.";
		
		if(!$this->pickExists())
			$errors[] = "Player doesn't exist.";
		
		if(!isset($this->first_name) || strlen($this->first_name) == 0)
			$errors[] = "Player must have a first name.";
		if(!isset($this->last_name) || strlen($this->last_name) == 0)
			$errors[] = "Player must have a last name.";
		if(!isset($this->team) || strlen($this->team) == 0 || strlen($draft->sports_teams[$this->team]) == 0)
			$errors[] = "Player has an invalid team.";
		if(!isset($this->position) || strlen($this->position) == 0 || strlen($draft->sports_positions[$this->position]) == 0)
			$errors[] = "Player has an invalid position.";
		
		return $errors;
	}
	
	public function updatePickDuration($previous_pick, draft_object $draft) {
		global $DBH; /* @var $DBH PDO */
		require_once('libraries/php_draft_library.php');
		
		if(!isset($this->pick_time) || strlen($this->pick_time) == 0)
			throw new Exception("Must call updatePickDuration on a player object that already has its own pick_time set!");
		
		if($this->player_pick == 1 || $previous_pick === false) 
			$start_time = strtotime($draft->draft_start_time);
		else
			$start_time = strtotime($previous_pick->pick_time);
		
		$now = strtotime($this->pick_time);
		
		$alloted_time = $now - $start_time;
		
		$this->pick_duration = (int)$alloted_time;
		
		$stmt = $DBH->prepare("UPDATE players SET pick_duration = ? WHERE player_id = ?");
		$stmt->bindParam(1, $alloted_time);
		$stmt->bindParam(2, $this->player_id);
		
		return $stmt->execute();
	}

	/**
	 * Get all players/picks for a given draft.
	 * @param int $draft_id ID of the draft to get players for
	 * @return array Player objects that belong to given draft. false on failure
	 */
	public static function getPlayersByDraft($draft_id) {
		global $DBH; /* @var $DBH PDO */
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$players_stmt = $DBH->prepare("SELECT * FROM players WHERE draft_id = ? ORDER BY player_pick ASC");
		$players_stmt->bindParam(1, $draft_id);
		
		$players_stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');

		$players = array();
		
		if(!$players_stmt->execute())
			return false;
		
		while($player = $players_stmt->fetch())
			$players[] = $player;

		return $players;
	}

	// <editor-fold defaultstate="collapsed" desc="Draft-Related Pick Functions">
	/**
	 * For a draft get the ten most recent picks that have occurred.
	 * @param int $draft_id
	 * @return array picks Array of picks, or false on error.
	 */
	public static function getLastTenPicks($draft_id) {
		global $DBH; /* @var $DBH PDO */
		$draft_id = (int)$draft_id;
		$picks = array();

		if($draft_id == 0)
			return false;

		$stmt = $DBH->prepare("SELECT p.*, m.manager_name, m.manager_id ".
				"FROM players p ".
				"LEFT OUTER JOIN managers m ".
				"ON m.manager_id = p.manager_id ".
				"WHERE p.draft_id = ? ".
				"AND p.pick_time IS NOT NULL ".
				"AND p.pick_duration IS NOT NULL ".
				"ORDER BY p.player_pick DESC LIMIT 10");
		
		$stmt->bindParam(1, $draft_id);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		while($player = $stmt->fetch())
			$picks[] = $player;
		
		return $picks;
	}
	
	/**
	 * Grab the last five completed draft picks
	 * @param draft_object $draft
	 * @return array last five picks 
	 */
	public static function getLastFivePicks(draft_object $draft) {
		global $DBH; /* @var $DBH PDO */
		$picks = array();
		
		$stmt = $DBH->prepare("SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ".
		"AND p.pick_time IS NOT NULL ".
		"ORDER BY p.player_pick DESC ".
		"LIMIT 5");
		
		$stmt->bindParam(1, $draft->draft_id);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		while($player = $stmt->fetch())
			$picks[] = $player;
		
		return $picks;
	}
	
	/**
	 * Get the previous (completed) pick in the draft
	 * @param draft_object $draft
	 * @return player_object $last_player, or false on 0 rows
	 */
	public static function getLastPick(draft_object $draft) {
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ".
		"AND p.player_pick = ? ".
		"AND p.pick_time IS NOT NULL ".
		"LIMIT 1");
		
		$stmt->bindParam(1, $draft->draft_id);
		$stmt->bindParam(2, $current_pick);
		
		$current_pick = ($draft->draft_current_pick - 1);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		return $stmt->fetch();
	}
	
	/**
	 * Called from a draft or statically from a presenter, gets the current pick "on the clock"
	 * @param draft_object $draft Object to get the current pick for
	 * @return player_object The current pick
	 */
	public static function getCurrentPick(draft_object $draft) {
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ".
		"AND p.player_round = ? ".
		"AND p.player_pick = ? ".
		"LIMIT 1");
		
		$stmt->bindParam(1, $draft->draft_id);
		$stmt->bindParam(2, $draft->draft_current_round);
		$stmt->bindParam(3, $draft->draft_current_pick);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		return $stmt->fetch();
	}
	
	/**
	 * Get the next pick object
	 * @param draft_object $draft
	 * @return player_object the next pick 
	 */
	public static function getNextPick(draft_object $draft) {
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
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		return $stmt->fetch();
	}
	
	/**
	 * Get the next five picks
	 * @param draft_object $draft
	 * @return array of player_objects 
	 */
	public static function getNextFivePicks(draft_object $draft) {
		global $DBH; /* @var $DBH PDO */
		$picks = array();
		
		$stmt = $DBH->prepare("SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ".
		"AND p.player_pick > ? ".
		"ORDER BY p.player_pick ASC ".
		"LIMIT 5");
		
		$stmt->bindParam(1, $draft->draft_id);
		$stmt->bindParam(2, $draft->draft_current_pick);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		while($player = $stmt->fetch())
			$picks[] = $player;
		
		return $picks;
	}
	
	// </editor-fold>

	/**
	 * Get all players/picks for a given manager that have been selected.
	 * @param int $manager_id ID of the manager to get players for
	 * @return array Player objects that belong to given manager. false on failure
	 */
	public static function getSelectedPlayersByManager($manager_id) {
		global $DBH; /* @var $DBH PDO */
		$manager_id = (int)$manager_id;

		if($manager_id == 0)
			return false;
		
		$stmt = $DBH->prepare("SELECT * FROM players WHERE manager_id = ? AND pick_time IS NOT NULL ORDER BY player_pick ASC");
		$stmt->bindParam(1, $manager_id);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		while($player = $stmt->fetch())
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
	public static function getSelectedPlayersByRound($draft_id, $round, $sort_ascending = true) {
		global $DBH; /* @var $DBH PDO */
		$players = array();
		$sortOrder = $sort_ascending ? "ASC" : "DESC";
		
		$draft_id = (int)$draft_id;
		$round = (int)$round;

		if($draft_id == 0 || $round == 0)
			return false;
		
		$stmt = $DBH->prepare("SELECT p.*, m.manager_name FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ".
		" AND p.player_round = ? AND p.pick_time IS NOT NULL ORDER BY p.player_pick " . $sortOrder);
		
		$stmt->bindParam(1, $draft_id);
		$stmt->bindParam(2, $round);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;
		
		while($player = $stmt->fetch())
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
	public static function getAllPlayersByRound($draft_id, $round, $sort = true) {
		global $DBH; /* @var $DBH PDO */
		$players = array();
		$sortOrder = $sort ? "ASC" : "DESC";
		
		$draft_id = (int)$draft_id;
		$round = (int)$round;

		if($draft_id == 0 || $round == 0)
			return false;
		
		$stmt = $DBH->prepare("SELECT p.*, m.manager_name FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = ? ". 
		" AND p.player_round = ? ORDER BY p.player_pick " . $sortOrder);
		
		$stmt->bindParam(1, $draft_id);
		$stmt->bindParam(2, $round);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		if($stmt->rowCount() == 0)
			return false;

		while($player = $stmt->fetch())
			$players[] = $player;

		return $players;
	}

	public static function deletePlayersByDraft($draft_id) {
		global $DBH; /* @var $DBH PDO */
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$players = player_object::getPlayersByDraft($draft_id);

		$id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.	

		foreach($players as $player) {
			$id_string .= "," . $player->player_id;
		}
		
		$stmt = $DBH->prepare("DELETE FROM players WHERE player_id IN (?)");
		$stmt->bindParam(1, $id_string);

		return $stmt->execute();
	}
	
	// <editor-fold defaultstate="collapsed" desc="State Information">
	public function hasName() {
		return (strlen($this->first_name) + strlen($this->last_name) > 1);
	}
	
	/**
	 * Check to ensure the pick exists in the database
	 * @return bool 
	 */
	public function pickExists() {
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT player_id FROM players WHERE player_id = ? AND draft_id = ? AND player_pick = ? AND player_round = ? LIMIT 1");
		$stmt->bindParam(1, $this->player_id);
		$stmt->bindParam(2, $this->draft_id);
		$stmt->bindParam(3, $this->player_pick);
		$stmt->bindParam(4, $this->player_round);
		
		if(!$stmt->execute())
			return false;
		
		return $stmt->rowCount() == 1;
	}
	
	/**
	 * Determine whether a player/pick has been selected. Generally determines if it is editable.
	 * @return bool true if selected 
	 */
	public function hasBeenSelected() {
		return isset($this->pick_time) && isset($this->pick_duration)
			&& strlen($this->pick_time) > 0 && $this->pick_duration > 0;
	}
	// </editor-fold>
}

?>
