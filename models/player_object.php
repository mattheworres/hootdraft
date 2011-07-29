<?php
require_once("/models/draft_object.php");
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
 * @property string $pick_time Timestamp of when the player was picked.
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
		if(intval($id) == 0)
			return false;

		$id = intval($id);

		$player_result = mysql_query("SELECT * FROM players WHERE player_id = " . $id . " LIMIT 1");

		if(!$player_row = mysql_fetch_array($player_result))
			return false;

		$this->player_id = intval($player_row['player_id']);
		$this->manager_id = intval($player_row['manager_id']);
		$this->draft_id = intval($player_row['draft_id']);
		$this->first_name = $player_row['first_name'];
		$this->last_name = $player_row['last_name'];
		$this->team = $player_row['team'];
		$this->position = $player_row['position'];
		$this->pick_time = strtotime($player_row['pick_time']);
		$this->pick_duration = intval($player_row['pick_duration']);
		$this->player_round = intval($player_row['player_round']);
		$this->player_pick = intval($player_row['player_pick']);

		return true;
	}

	/**
	 * Saves or updates a player. NOTE: Does not update or save the player's pick time or pick duration. Those must be handled separately.
	 * @return bool true on success, false otherwise 
	 */
	public function savePlayer() {
		if($this->player_id > 0) {
			//TODO: Add a default value variable to trigger whether or not to set the pick time on player update. $setPickToNow = false
			$sql = "UPDATE players SET " .
				"manager_id = " . intval($this->manager_id) . ", " .
				"draft_id = " . intval($this->draft_id) . ", " .
				"first_name = " . mysql_real_escape_string($this->first_name) . ", " .
				"last_name = " . mysql_real_escape_string($this->last_name) . ", " .
				"team = " . mysql_real_escape_string($this->team) . ", " .
				"position = " . mysql_real_escape_string($this->position) . ", " .
				"player_round = " . intval($this->player_round) . ", " .
				"player_pick = " . intval($this->player_pick) . ", " .
				"WHERE player_id = " . intval($this->player_id);
			return mysql_query($sql);
		} elseif($this->draft_id > 0 && $this->manager_id > 0) {
			//TODO: Investigate how to insert with empty fields.
			$sql = "INSERT INTO players " .
				"(manager_id, draft_id, player_round, player_pick) " .
				"VALUES " .
				"(" . intval($this->manager_id) . ", " . intval($this->draft_id) . ", " . intval($this->player_round) . ", " . intval($this->player_pick) . ")";

			$result = mysql_query($sql);
			if(!$result)
				return false;

			$this->player_id = mysql_insert_id();

			return true;
		}else
			return false;
	}

	/**
	 * Get all players/picks for a given draft.
	 * @param int $draft_id ID of the draft to get players for
	 * @return array Player objects that belong to given draft. false on failure
	 */
	public static function getPlayersByDraft($draft_id) {
		$draft_id = intval($draft_id);

		if($draft_id == 0)
			return false;

		$players_result = mysql_query("SELECT * FROM players WHERE draft_id = " . $draft_id . " ORDER BY player_pick ASC");

		$players = array();

		while($player_row = mysql_fetch_array($players_result)) {
			$player = new player_object();
			$player->player_id = intval($player_row['player_id']);
			$player->manager_id = intval($player_row['manager_id']);
			$player->draft_id = intval($player_row['draft_id']);
			$player->first_name = $player_row['first_name'];
			$player->last_name = $player_row['last_name'];
			$player->team = $player_row['team'];
			$player->position = $player_row['position'];
			$player->pick_time = strtotime($player_row['pick_time']);
			$player->pick_duration = intval($player_row['pick_duration']);
			$player->player_round = intval($player_row['player_round']);
			$player->player_pick = intval($player_row['player_pick']);
			$players[] = $player;
		}

		return $players;
	}

	// <editor-fold defaultstate="collapsed" desc="Draft-Related Pick Functions">
	/**
	 * For a draft get the ten most recent picks that have occurred.
	 * @param int $draft_id
	 * @return array picks Array of picks, or false on error.
	 */
	public static function getLastTenPicks($draft_id) {
		$draft_id = intval($draft_id);

		if($draft_id == 0)
			return false;

		$sql = "SELECT *, m.manager_name, m.manager_id ".
				"FROM players ".
				"LEFT OUTER JOIN managers m ".
				"ON m.manager_id = players.manager_id ".
				"WHERE players.draft_id = " . $draft_id . " ".
				"AND pick_time IS NOT NULL ".
				"AND pick_duration IS NOT NULL ".
				"ORDER BY player_pick DESC LIMIT 10";

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
		"WHERE p.draft_id = " . $draft->draft_id . " ".
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
	 * Called from a draft or statically from a presenter, gets the current pick "on the clock"
	 * @param draft_object $draft Object to get the current pick for
	 * @return player_object The current pick
	 */
	public static function getCurrentPick(draft_object $draft) {
		$sql = "SELECT p.*, m.* ".
		"FROM players p ".
		"LEFT OUTER JOIN managers m ".
		"ON m.manager_id = p.manager_id ".
		"WHERE p.draft_id = " . $draft->draft_id . " ".
		"AND p.player_round = " . $draft->current_round . " ".
		"AND p.player_pick = " . $draft->current_pick . " ".
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
		"WHERE p.draft_id = " . $draft->draft_id . " " .
		"AND p.player_pick = " . ($draft->current_pick + 1) . " LIMIT 1";
		
		$pick_row = mysql_fetch_array(mysql_query($sql));
		
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
		"WHERE p.draft_id = " . $draft->draft_id . " ".
		"AND p.player_pick > " . $draft->current_pick . " ".
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
		$manager_id = intval($manager_id);

		if($manager_id == 0)
			return false;

		$players_result = mysql_query("SELECT * FROM players WHERE manager_id = " . $manager_id . " AND pick_time IS NOT NULL ORDER BY player_pick ASC");

		$players = array();

		while($player_row = mysql_fetch_array($players_result)) {
			$player = new player_object();
			$player->player_id = intval($player_row['player_id']);
			$player->manager_id = intval($player_row['manager_id']);
			$player->draft_id = intval($player_row['draft_id']);
			$player->first_name = $player_row['first_name'];
			$player->last_name = $player_row['last_name'];
			$player->team = $player_row['team'];
			$player->position = $player_row['position'];
			$player->pick_time = strtotime($player_row['pick_time']);
			$player->pick_duration = intval($player_row['pick_duration']);
			$player->player_round = intval($player_row['player_round']);
			$player->player_pick = intval($player_row['player_pick']);
			$players[] = $player;
		}

		return $players;
	}

	/**
	 * Get all selected players for a given round.
	 * @param int $draft_id ID of the draft for the given round
	 * @param int $round Round to get players for
	 * @return array Player objects that belong in a given round. false on failure
	 */
	public static function getSelectedPlayersByRound($draft_id, $round) {
		$draft_id = intval($draft_id);
		$round = intval($round);

		if($draft_id == 0 || $round == 0)
			return false;

		$players_result = mysql_query("SELECT * FROM players WHERE draft_id = " . $manager_id . " AND round = " . $round . " AND pick_time IS NOT NULL ORDER BY player_pick ASC");

		$players = array();

		while($player_row = mysql_fetch_array($players_result)) {
			$player = new player_object();
			$player->player_id = intval($player_row['player_id']);
			$player->manager_id = intval($player_row['manager_id']);
			$player->draft_id = intval($player_row['draft_id']);
			$player->first_name = $player_row['first_name'];
			$player->last_name = $player_row['last_name'];
			$player->team = $player_row['team'];
			$player->position = $player_row['position'];
			$player->pick_time = strtotime($player_row['pick_time']);
			$player->pick_duration = intval($player_row['pick_duration']);
			$player->player_round = intval($player_row['player_round']);
			$player->player_pick = intval($player_row['player_pick']);
			$players[] = $player;
		}

		return $players;
	}

	public static function deletePlayersByDraft($draft_id) {
		$draft_id = intval($draft_id);

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
	
	// <editor-fold defaultstate="collapsed" desc="State Information">
	public function hasName() {
		return (strlen($this->first_name) + strlen($this->last_name) > 0);
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Private Object Helpers">
	/**
	 * This might be bad practice, but seemed that I was duplicating a TON of code for each query I ran on players and managers.
	 * Given a single mysql row, fill a new player object and return it.
	 * @param array $mysql_array Filled mysql row of player-manager data
	 * @return player_object new player_object filled with data.
	 */
	private static function fillPlayerObject($mysql_array, $draft_id) {
		$player = new player_object();
		
		$player->draft_id = $draft_id;
		$player->player_id = intval($mysql_array['player_id']);
		$player->manager_id = intval($mysql_array['manager_id']);
		$player->manager_name = $mysql_array['manager_name'];
		$player->first_name = $mysql_array['first_name'];
		$player->last_name = $mysql_array['last_name'];
		$player->position = $mysql_array['position'];
		$player->team = $mysql_array['team'];
		$player->pick_time = strtotime($mysql_array['pick_time']);
		$player->pick_duration = intval($mysql_array['pick_duration']);
		$player->player_round = intval($mysql_array['player_round']);
		$player->player_pick = intval($mysql_array['player_pick']);
		
		return $player;
	}
	// </editor-fold>
}

?>
