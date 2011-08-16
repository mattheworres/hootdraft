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
		if((int)$id == 0)
			return false;

		$id = (int)$id;

		$player_result = mysql_query("SELECT * FROM players WHERE player_id = " . $id . " LIMIT 1");

		if(!$player_row = mysql_fetch_array($player_result))
			return false;

		$this->player_id = (int)$player_row['player_id'];
		$this->manager_id = (int)$player_row['manager_id'];
		$this->draft_id = (int)$player_row['draft_id'];
		$this->first_name = $player_row['first_name'];
		$this->last_name = $player_row['last_name'];
		$this->team = $player_row['team'];
		$this->position = $player_row['position'];
		$this->pick_time = $player_row['pick_time'];
		$this->pick_duration = (int)$player_row['pick_duration'];
		$this->player_round = (int)$player_row['player_round'];
		$this->player_pick = (int)$player_row['player_pick'];

		return true;
	}

	/**
	 * Saves or updates a player. NOTE: Does not update or save the player's pick time or pick duration. Those must be handled separately.
	 * @return bool true on success, false otherwise 
	 */
	public function savePlayer($setPickToNow = false) {
		if($this->player_id > 0) {
			$sql = "UPDATE players SET " .
				"manager_id = " . (int)$this->manager_id . ", " .
				"draft_id = " . (int)$this->draft_id . ", " .
				"first_name = '" . mysql_real_escape_string($this->first_name) . "', " .
				"last_name = '" . mysql_real_escape_string($this->last_name) . "', " .
				"team = '" . mysql_real_escape_string($this->team) . "', " .
				"position = '" . mysql_real_escape_string($this->position) . "', " .
				"player_round = " . (int)$this->player_round . ", " .
				"player_pick = " . (int)$this->player_pick . " ";
			
			if($setPickToNow == true) {
				$now = php_draft_library::getNowPhpTime();
				$this->pick_time = $now;
				$sql .= ", pick_time = '" . mysql_real_escape_string($now) . "' ";
			}
			
			$sql .= "WHERE player_id = " . (int)$this->player_id;
			return mysql_query($sql);
		} elseif($this->draft_id > 0 && $this->manager_id > 0) {
			//TODO: Investigate how to insert with empty fields.
			$sql = "INSERT INTO players " .
				"(manager_id, draft_id, player_round, player_pick) " .
				"VALUES " .
				"(" . (int)$this->manager_id . ", " . (int)$this->draft_id . ", " . (int)$this->player_round . ", " . (int)$this->player_pick . ")";

			$result = mysql_query($sql);
			if(!$result)
				return false;

			$this->player_id = mysql_insert_id();

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
		require_once('libraries/php_draft_library.php');
		
		if(!isset($this->pick_time))
			throw new Exception("Must call updatePickDuration on a player object that already has its own pick_time set!");
		
		if($this->player_pick == 1 || $previous_pick === false) 
			$start_time = strtotime($draft->draft_start_time);
		else
			$start_time = strtotime($previous_pick->pick_time);
		
		$now = strtotime($this->pick_time);
		
		$alloted_time = $now - $start_time;
		
		$this->pick_duration = (int)$alloted_time;
		
		$sql = "UPDATE players SET pick_duration = " . (int)$alloted_time . " WHERE player_id = " . (int)$this->player_id;
		
		return mysql_query($sql);
	}

	/**
	 * Get all players/picks for a given draft.
	 * @param int $draft_id ID of the draft to get players for
	 * @return array Player objects that belong to given draft. false on failure
	 */
	public static function getPlayersByDraft($draft_id) {
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$players_result = mysql_query("SELECT * FROM players WHERE draft_id = " . $draft_id . " ORDER BY player_pick ASC");

		$players = array();

		while($player_row = mysql_fetch_array($players_result))
			$players[] = player_object::fillPlayerObject($player_row, $draft_id);

		return $players;
	}

	// <editor-fold defaultstate="collapsed" desc="Draft-Related Pick Functions">
	/**
	 * For a draft get the ten most recent picks that have occurred.
	 * @param int $draft_id
	 * @return array picks Array of picks, or false on error.
	 */
	public static function getLastTenPicks($draft_id) {
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$sql = "SELECT p.*, m.manager_name, m.manager_id ".
				"FROM players p ".
				"LEFT OUTER JOIN managers m ".
				"ON m.manager_id = p.manager_id ".
				"WHERE p.draft_id = " . $draft_id . " ".
				"AND p.pick_time IS NOT NULL ".
				"AND p.pick_duration IS NOT NULL ".
				"ORDER BY p.player_pick DESC LIMIT 10";

		$pick_result = mysql_query($sql);
		
		$picks = array();
		
		while($pick_row = mysql_fetch_array($pick_result)) {
			$picks[] = player_object::fillPlayerObject($pick_row, $draft_id);
		}
		
		return $picks;
	}
	
	/**
	 * Grab the last five completed draft picks
	 * @param draft_object $draft
	 * @return array last five picks 
	 */
	public static function getLastFivePicks(draft_object $draft) {
		$sql = "SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . (int)$draft->draft_id . " ".
		"AND p.pick_time IS NOT NULL ".
		"ORDER BY p.player_pick DESC ".
		"LIMIT 5";
		
		$pick_result = mysql_query($sql);
		
		$picks = array();
		
		while($pick_row = mysql_fetch_array($pick_result))
			$picks[] = player_object::fillPlayerObject($pick_row, $draft->draft_id);
		
		return $picks;
	}
	
	/**
	 * Get the previous (completed) pick in the draft
	 * @param draft_object $draft
	 * @return player_object $last_player, or false on 0 rows
	 */
	public static function getLastPick(draft_object $draft) {
		$sql = "SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . (int)$draft->draft_id .
		" AND p.player_pick = " . (int)($draft->draft_current_pick - 1) .
		" AND p.pick_time IS NOT NULL ".
		"LIMIT 1";
		
		$pick_result = mysql_query($sql);
		
		if(mysql_num_rows($pick_result) == 0)
			return false;
		
		$pick_row = mysql_fetch_array($pick_result);
		
		return player_object::fillPlayerObject($pick_row, $draft->draft_id);
	}
	
	/**
	 * Called from a draft or statically from a presenter, gets the current pick "on the clock"
	 * @param draft_object $draft Object to get the current pick for
	 * @return player_object The current pick
	 */
	public static function getCurrentPick(draft_object $draft) {
		$sql = "SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . (int)$draft->draft_id . " ".
		"AND p.player_round = " . (int)$draft->draft_current_round . " ".
		"AND p.player_pick = " . (int)$draft->draft_current_pick . " ".
		"LIMIT 1";
		
		$pick_row = mysql_fetch_array(mysql_query($sql));
		
		return player_object::fillPlayerObject($pick_row, $draft->draft_id);
	}
	
	/**
	 * Get the next pick object
	 * @param draft_object $draft
	 * @return player_object the next pick 
	 */
	public static function getNextPick(draft_object $draft) {
		$sql = "SELECT p.*, m.* " .
		"FROM players p " .
		"LEFT OUTER JOIN managers m " .
		"ON m.manager_id = p.manager_id " .
		"WHERE p.draft_id = " . (int)$draft->draft_id . " " .
		"AND p.player_pick = " . (int)($draft->draft_current_pick + 1) . " LIMIT 1";
		
		$pick_result = mysql_query($sql);
		
		if(mysql_num_rows($pick_result) == 0)
			return false;
		
		$pick_row = mysql_fetch_array($pick_result);
		
		return player_object::fillPlayerObject($pick_row, $draft->draft_id);
	}
	
	/**
	 * Get the next five picks
	 * @param draft_object $draft
	 * @return array of player_objects 
	 */
	public static function getNextFivePicks(draft_object $draft) {
		$sql = "SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . (int)$draft->draft_id . " ".
		"AND p.player_pick > " . (int)$draft->draft_current_pick . " ".
		"ORDER BY p.player_pick ASC ".
		"LIMIT 5";
		
		$pick_result = mysql_query($sql);
		
		$picks = array();
		
		while($pick_row = mysql_fetch_array($pick_result))
			$picks[] = player_object::fillPlayerObject($pick_row, $draft->draft_id);
		
		return $picks;
	}
	
	// </editor-fold>

	/**
	 * Get all players/picks for a given manager that have been selected.
	 * @param int $manager_id ID of the manager to get players for
	 * @return array Player objects that belong to given manager. false on failure
	 */
	public static function getSelectedPlayersByManager($manager_id) {
		$manager_id = (int)$manager_id;

		if($manager_id == 0)
			return false;

		$players_result = mysql_query("SELECT * FROM players WHERE manager_id = " . $manager_id . " AND pick_time IS NOT NULL ORDER BY player_pick ASC");

		$players = array();

		while($player_row = mysql_fetch_array($players_result))
			$players[] = player_object::fillPlayerObject($player_row);

		return $players;
	}

	/**
	 * Get all selected players for a given round.
	 * @param int $draft_id ID of the draft for the given round
	 * @param int $round Round to get players for
	 * @param bool $sort Whether to sort by ASC or not. False == DESC
	 * @return array Player objects that belong in a given round. false on failure
	 */
	public static function getSelectedPlayersByRound($draft_id, $round, $sort = true) {
		$sortOrder = $sort ? "ASC" : "DESC";
		
		$draft_id = (int)$draft_id;
		$round = (int)$round;

		if($draft_id == 0 || $round == 0)
			return false;
		
		$sql = "SELECT p.*, m.* FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . (int)$draft_id . 
		" AND p.player_round = " . (int)$round . " AND p.pick_time IS NOT NULL ORDER BY p.player_pick " . $sortOrder;

		$players_result = mysql_query($sql);

		$players = array();

		while($player_row = mysql_fetch_array($players_result)) {
			$players[] = player_object::fillPlayerObject($player_row, $draft_id);
		}

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
		$sortOrder = $sort ? "ASC" : "DESC";
		
		$draft_id = (int)$draft_id;
		$round = (int)$round;

		if($draft_id == 0 || $round == 0)
			return false;
		
		$sql = "SELECT p.*, m.* FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . $draft_id . 
		" AND p.player_round = " . $round . " ORDER BY p.player_pick " . $sortOrder;

		$players_result = mysql_query($sql);

		$players = array();

		while($player_row = mysql_fetch_array($players_result)) {
			$players[] = player_object::fillPlayerObject($player_row, $draft_id);
		}

		return $players;
	}

	public static function deletePlayersByDraft($draft_id) {
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$players = player_object::getPlayersByDraft($draft_id);

		$id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.	

		foreach($players as $player) {
			$id_string .= "," . $player->player_id;
		}

		$sql = "DELETE FROM players WHERE player_id IN (" . mysql_escape_string($id_string) . ")";

		return mysql_query($sql);
	}
	
	/**
	 * Searches for picked players with strict criteria, using the MATCH() and score method. Sorts by score ASC first, then pick DESC last.
	 * @param search_object $search Criteria object searched on
	 * @param int $draft_id 
	 */
	public static function searchPlayersByStrictCriteria(search_object $search, $draft_id) {
		$draft_id = (int)$draft_id;
		$search->keywords = mysql_real_escape_string($search->keywords);
		$search->team = mysql_real_escape_string($search->team);
		$search->position = mysql_real_escape_string($search->position);
		
		$sql = "SELECT p.*, m.*, MATCH (p.first_name, p.last_name) AGAINST ('" . $search->keywords . "') as score ".
			"FROM players p ".
			"LEFT OUTER JOIN managers m ".
			"ON m.manager_id = p.manager_id ".
			"WHERE MATCH (p.first_name, p.last_name) AGAINST ('" . $search->keywords . "') ".
			"AND p.draft_id = " . $draft_id . " ";
		
		if($search->hasTeam())
			$sql .= "AND p.team = '" . $search->team . "' ";
		
		if($search->hasPosition())
			$sql .= "AND p.position = '" . $search->position . "' ";
		
		$sql .= "AND p.pick_time IS NOT NULL ORDER BY score ASC, p.player_pick DESC";
		
		$search_result = mysql_query($sql);
		
		$players = array();
		
		while($player_row = mysql_fetch_array($search_result))
			$players[] = player_object::fillPlayerObject($player_row, $draft_id);
		
		$search->player_results = $players;
		$search->search_count = count($players);
	}
	
	/**
	 * Search picked players by a loose criteria that uses a LIKE %% query. Used if strict query returns 0 results. Sorts by pick DESC.
	 * @param search_object $search Criteria object searched on
	 * @param int $draft_id 
	 */
	public static function searchPlayersByLooseCriteria(search_object $search, $draft_id) {
		$draft_id = (int)$draft_id;
		$search->keywords = mysql_real_escape_string($search->keywords);
		$search->team = mysql_real_escape_string($search->team);
		$search->position = mysql_real_escape_string($search->position);
		
		$sql = "SELECT p.*, m.* ".
			"FROM players p ".
			"LEFT OUTER JOIN managers m ".
			"ON m.manager_id = p.manager_id ".
			"WHERE p.draft_id = " . $draft_id . " ";
		
		if($search->hasName())	
			$sql .= "AND (p.first_name LIKE '%" . $search->keywords . "%'".
				"OR p.last_name LIKE '%" . $search->keywords . "%')";
		
		if($search->hasTeam())
			$sql .= "AND p.team = '" . $search->team . "' ";
		
		if($search->hasPosition())
			$sql .= "AND p.position = '" . $search->position . "' ";
		
		$sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick DESC";
		
		$search_result = mysql_query($sql);
		
		$players = array();
		
		while($player_row = mysql_fetch_array($search_result))
			$players[] = player_object::fillPlayerObject($player_row, $draft_id);
		
		$search->player_results = $players;
		$search->search_count = count($players);
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
		$sql = "SELECT player_id FROM players WHERE player_id = ". 
		(int)$this->player_id . " AND draft_id = " . (int)$this->draft_id . " AND ".
		"player_pick = " . (int)$this->player_pick . " AND player_round = " . (int)$this->player_round . " LIMIT 1";
		
		return (mysql_num_rows(mysql_query($sql)) == 1);
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
	
	// <editor-fold defaultstate="collapsed" desc="Private Object Helpers">
	/**
	 * This might be bad practice, but seemed that I was duplicating a TON of code for each query I ran on players and managers.
	 * Given a single mysql row, fill a new player object and return it.
	 * @param array $mysql_array Filled mysql row of player-manager data
	 * @return player_object new player_object filled with data.
	 */
	private static function fillPlayerObject($mysql_array, $draft_id = 0) {
		$player = new player_object();
		
		if($draft_id > 0)
			$player->draft_id = $draft_id;
		else
			$player->draft_id = (int)$mysql_array['draft_id'];
		
		$player->player_id = (int)$mysql_array['player_id'];
		$player->manager_id = (int)$mysql_array['manager_id'];
		$player->manager_name = $mysql_array['manager_name'];
		$player->first_name = $mysql_array['first_name'];
		$player->last_name = $mysql_array['last_name'];
		$player->position = $mysql_array['position'];
		$player->team = $mysql_array['team'];
		$player->pick_time = $mysql_array['pick_time'];
		$player->pick_duration = (int)$mysql_array['pick_duration'];
		$player->player_round = (int)$mysql_array['player_round'];
		$player->player_pick = (int)$mysql_array['player_pick'];
		
		return $player;
	}
	// </editor-fold>
}

?>
