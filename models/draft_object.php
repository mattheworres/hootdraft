<?php

require_once("libraries/php_draft_library.php");
require_once("models/player_object.php");

/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 *
 * @property int $draft_id The unique identifier for this draft
 * @property string $draft_name The string identifier for this draft
 * @property string $draft_status The description of the status is in
 * @property string $draft_password Determines draft visibility
 * @property string $draft_sport
 * @property string $draft_style Either 'serpentine' or 'standard'
 * @property int $draft_rounds Number of rounds draft will have in total
 * @property string $draft_start_time datetime of when the draft was put into "started" status
 * @property string $draft_end_time datetime of when the draft was put into "completed" status
 * @property int $draft_current_round
 * @property int $draft_current_pick
 * @property array $sports_teams An array of all of the teams in the pro sport. Capitalized abbreviation is key, full name is value.
 * @property array $sports_positions An array of all the positions in the pro sport. Capitalized abbreviation is key, full name is value.
 * @property array $sports_colors An array of all the colors used for each position in the draft. Capitalized position abbreviation is key, hex color string is value (with # prepended)
 */
class draft_object {

	public $draft_id;
	public $draft_name;
	public $draft_sport;
	public $draft_status;
	public $draft_style;
	public $draft_rounds;
	public $draft_password;
	public $draft_start_time;
	public $draft_end_time;
	public $draft_current_round;
	public $draft_current_pick;
	public $sports_teams;
	public $sports_positions;
	public $sports_colors;

	public function __construct($id = 0) {
		global $DBH; /* @var $DBH PDO */
		$id = (int)$id;
		
		if($id == 0)
			return false;
		
		$draft_stmt = $DBH->prepare("SELECT * FROM draft WHERE draft_id = ? LIMIT 1");
		$draft_stmt->setFetchMode(PDO::FETCH_INTO, $this);
		$draft_stmt->bindParam(1, $id);
		
		if(!$draft_stmt->execute())
			return false;
		
		if(!$draft_stmt->fetch())
			return false;

		return true;
	}

	/**
	 * Check the validity of parent draft object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity() {
		$errors = array();

		if(!isset($this->draft_name) || strlen($this->draft_name) == 0)
			$errors[] = "Draft Name is empty.";
		if(!isset($this->draft_name) || strlen($this->draft_sport) == 0)
			$errors[] = "Draft Sport is empty.";
		if(!isset($this->draft_style) || strlen($this->draft_style) == 0)
			$errors[] = "Draft Style is empty.";

		if($this->draft_rounds < 1)
			$errors[] = "Draft rounds must be at least 1 or more.";

		if(empty($this->draft_id) || $this->draft_id == 0) {
			global $DBH; /* @var $DBH PDO */
			
			$name_stmt = $DBH->prepare("SELECT COUNT(draft_id) as count FROM draft where draft_name = ? AND draft_sport = ?");
			$name_stmt->bindParam(1, $this->draft_name);
			$name_stmt->bindParam(2, $this->draft_sport);
			
			if(!$name_stmt->execute())
				$errors[] = "Draft unable to be saved.";
			if(!$row = $name_stmt->fetch())
				$errors[] = "Draft unable to be saved.";
			
			$name_count = (int)$row['count'];

			if($name_count > 0)
				$errors[] = "Draft already found with that name and sport.";
		}

		return $errors;
	}

	/**
	 * Grab the formatted string corresponding to the draft's status
	 * @param $draft_status_from_database The textual status stored in the database
	 */
	public function getStatus() {
		switch($this->draft_status) {
			case "undrafted":
				return "Setting Up";
				break;

			case "in_progress":
				return "Currently Drafting";
				break;

			case "complete":
				return "Draft Complete";
				break;
		}
	}
	
	/**
	 * Returns a string representation of the time span of this draft
	 * @return string String representation of the duration of this draft
	 */
	public function getDraftDuration() {
		if($this->isCompleted()) {
			$duration_seconds = (int)strtotime($this->draft_end_time) - (int)strtotime($this->draft_start_time);
			return php_draft_library::secondsToWords($duration_seconds);
		} else if($this->isInProgress()) {
			$duration_seconds = (int)php_draft_library::getNowUnixTimestamp() - (int)strtotime($this->draft_start_time);
			return php_draft_library::secondsToWords($duration_seconds);
		}else
			return "";
	}

	/**
	 * Adds a new instance of this draft to the database
	 * @return boolean success whether or not the MySQL transaction succeeded.
	 * TODO: Update this so we so something similar to a Save-or-update, either an INSERT or an UPDATE
	 */
	public function saveDraft() {
		global $DBH; /* @var $DBH PDO */
		if($this->draft_id > 0) {
			$update_stmt = $DBH->prepare("UPDATE draft 
				SET draft_name = ?, draft_sport = ?, draft_status = ?, draft_style = ?,
					draft_rounds = ?, draft_current_round = ?, draft_current_pick = ?, 
					draft_password = ? 
				WHERE draft_id = ?");
			
			$update_stmt->bindParam(1, $this->draft_name);
			$update_stmt->bindParam(2, $this->draft_sport);
			$update_stmt->bindParam(3, $this->draft_status);
			$update_stmt->bindParam(4, $this->draft_style);
			$update_stmt->bindParam(5, $this->draft_rounds);
			$update_stmt->bindParam(6, $this->draft_current_round);
			$update_stmt->bindParam(7, $this->draft_current_pick);
			$update_stmt->bindParam(8, $this->draft_password);
			$update_stmt->bindParam(9, $this->draft_id);
			
			$result = $update_stmt->execute();
			return $result;
		}else {
			$insert_stmt = $DBH->prepare("INSERT INTO draft 
				(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds) 
				VALUES 
				(NULL, ?, ?, 'undrafted', ?, ?)");
			
			$insert_stmt->bindParam(1, $this->draft_name);
			$insert_stmt->bindParam(2, $this->draft_sport);
			$insert_stmt->bindParam(3, $this->draft_style);
			$insert_stmt->bindParam(4, $this->draft_rounds);
			
			if(!$insert_stmt->execute())
				return false;
			
			$this->draft_id = (int)$DBH->lastInsertId();		

			return true;
		}
	}
	
	public function moveDraftForward(player_object $next_pick) {
		if($next_pick != null) {
			$this->draft_current_pick = (int)$next_pick->player_pick;
			$this->draft_current_round = (int)$next_pick->player_round;
			
			$sql = "UPDATE draft SET ".
			"draft_current_pick = " . (int)$this->draft_current_pick . ", ".
			"draft_current_round = " . (int)$this->draft_current_round . " ".
			"WHERE draft_id = " . (int)$this->draft_id;
			
			return mysql_query($sql);
		}else {
			$sql = "UPDATE draft SET ".
			"draft_status = 'complete', ".
			"draft_end_time = '" . mysql_real_escape_string(php_draft_library::getNowPhpTime()) . "' ".
			"WHERE draft_id = " . (int)$this->draft_id;
		}
	}

	public function updateStatus($new_status) {
		if($this->isCompleted())
			return false;

		$old_status = $this->draft_status;

		$this->draft_status = $new_status;
		$this->draft_current_pick = 1;
		$this->draft_current_round = 1;

		$draftJustStarted = ($old_status == "undrafted" && $this->isInProgress()) ? true : false;

		if($draftJustStarted)
			$this->draft_start_time = $this->beginStartTime();
		else
			$this->draft_start_time = "NULL";

		$saveSuccess = $this->saveDraft();

		if(!$saveSuccess)
			return false;

		require_once("models/player_object.php");

		$deleteCurrentSuccess = player_object::deletePlayersByDraft($this->draft_id);

		if(!$deleteCurrentSuccess)
			return false;

		if($draftJustStarted) {
			$setupSuccess = $this->setupPicks();

			if(!$setupSuccess)
				return false;
		}

		return true;
	}

	/**
	 * Goes through and creates all of the draft's picks as placeholders, triggered when the draft status is set to "in progress"
	 * @return bool $success True if successful 
	 */
	public function setupPicks() {
		require_once("models/manager_object.php");
		require_once("models/player_object.php");
		$pick = 1;
		$even = true;
		
		for($current_round = 1; $current_round <= $this->draft_rounds; $current_round++) {
			if($this->styleIsSerpentine()) {
				if($even) {
					$managers = manager_object::getManagersByDraft($this->draft_id, true);
					$even = false;
				} else {
					$managers = manager_object::getManagersByDraft($this->draft_id, true, "DESC");
				}
			}else
				$managers = manager_object::getManagersByDraft($this->draft_id, true);

			foreach($managers as $manager) {
				$new_pick = new player_object();
				$new_pick->manager_id = $manager->manager_id;
				$new_pick->draft_id = $this->draft_id;
				$new_pick->player_round = $current_round;
				$new_pick->player_pick = $pick;

				$saveSuccess = $new_pick->savePlayer();
				
				if(!$saveSuccess)
					return false;

				$pick++;
			}
		}
		return true;
	}
	
	public function getAllDraftPicks() {
		$picks = array();
		
		$sort = true;
		for($i = 1; $i <= $this->draft_rounds; ++$i) {
			if($this->styleIsSerpentine()) {
				$picks[] = player_object::getAllPlayersByRound($this->draft_id, $i, $sort);
				$sort = $sort ? false : true;
			}else{
				$picks[] = player_object::getAllPlayersByRound($this->draft_id, $i);
			}
		}
		
		return $picks;
	}
	
	/**
	 * Grab proper array values for Teams and Positions dropdowns, and corresponding colors for positions too. Void function, operates on calling object.
	 */
	public function setupSport() {
		require_once("libraries/sports_values_library.php");
		$lib = new sports_values_library();
		$this->sports_teams = $lib->getTeams($this->draft_sport);
		$this->sports_positions = $lib->getPositions($this->draft_sport);
		$this->sports_colors = $lib->position_colors;
	}
	
	/**
	 * Using MySQL's NOW() function, set the draft's start time to NOW in database and return that value.
	 * @return string MySQL timestamp given to draft 
	 */
	public function beginStartTime() {
		$sql = "UPDATE draft SET draft_start_time = NOW() WHERE draft_id = " . (int)$this->draft_id . " LIMIT 1";
		mysql_query($sql);

		$time_row = mysql_fetch_array(mysql_query("SELECT draft_start_time FROM draft WHERE draft_id = " . (int)$this->draft_id . " LIMIT 1"));

		return $time_row['draft_start_time'];
	}
	
	/**
	 * Removes a draft, all of its managers and all of their picks permanently (hard delete)
	 * @return bool success of delete 
	 */
	public function deleteDraft() {
		if($this->draft_id == 0)
			return false;
		
		require_once("models/player_object.php");
		require_once("models/manager_object.php");
		
		$pickRemovalSuccess = player_object::deletePlayersByDraft($this->draft_id);
		
		if(!$pickRemovalSuccess)
			return false;
		
		$managerRemovalSuccess = manager_object::deleteManagersByDraft($this->draft_id);
		
		if(!$managerRemovalSuccess)
			return false;
		
		$sql = "DELETE FROM draft WHERE draft_id = " . (int)$this->draft_id . " LIMIT 1";
		
		return mysql_query($sql);
	}
	
	public function checkDraftPublicLogin() {
		return isset($_SESSION['did']) && isset($_SESSION['draft_password']) 
			&& $_SESSION['did'] == $this->draft_id 
			&& $_SESSION['draft_password'] == $this->draft_password;
	}
	
	// <editor-fold defaultstate="collapsed" desc="Pick-Related Functions">
	/**
	 * Returns an array of the last five picks (player_object) 
	 */
	public function getLastTenPicks() {
		return player_object::getLastTenPicks($this->draft_id);
	}
	
	/**
	 * Returns an array of the last five picks (player_object) 
	 */
	public function getLastFivePicks() {
		return player_object::getLastFivePicks($this);
	}
	
	/**
	 * Get the last player pick, or false on 0 rows.
	 * @return player_object Last Player
	 */
	public function getLastPick() {
		return player_object::getLastPick($this);
	}
	
	/**
	 * Returns the player_object that is the current pick for the draft.
	 */
	public function getCurrentPick() {
		return player_object::getCurrentPick($this);
	}
	
	/**
	 *
	 * @return type 
	 */
	public function getNextPick() {
		return player_object::getNextPick($this);
	}
	
	/**
	 * Returns an array of five player_object that occur in the future
	 */
	public function getNextFivePicks() {
		return player_object::getNextFivePicks($this);
	}
	// </editor-fold>
	
	/**
	 * Check to ensure $status is in the correct state to prevent any borking of the database.\
	 * @param string $status The database value for status to be checked
	 * @return bool true if status is legitimate, false otherwise
	 */
	public static function checkStatus($status) {
		if($status == "undrafted"
			|| $status == "in_progress"
			|| $status == "complete")
			return true;
		else
			return false;
	}

	/**
	 * Returns an array of all current drafts in the database
	 * @return array of all available draft objects
	 */
	public static function getAllDrafts() {
		$drafts = array();
		//TODO: Change draft object to include timestamp for creation; IDs are not technically reliable sortable columns.
		$sql = "SELECT * FROM draft ORDER BY draft_id";
		$drafts_result = mysql_query($sql);

		while($draft_row = mysql_fetch_array($drafts_result)) {
			$draft = new draft_object();
			$draft->draft_id = (int)$draft_row['draft_id'];
			$draft->draft_name = $draft_row['draft_name'];
			$draft->draft_status = $draft_row['draft_status'];
			$draft->draft_password = $draft_row['draft_password'];
			$draft->draft_sport = $draft_row['draft_sport'];
			$draft->draft_style = $draft_row['draft_style'];
			$draft->draft_rounds = (int)$draft_row['draft_rounds'];
			$draft->draft_start_time = $draft_row['draft_start_time'];
			$draft->draft_end_time = $draft_row['draft_end_time'];
			$draft->draft_current_round = (int)$draft_row['draft_current_round'];
			$draft->draft_current_pick = (int)$draft_row['draft_current_pick'];
			$drafts[] = $draft;
		}

		return $drafts;
	}

	// <editor-fold defaultstate="collapsed" desc="Draft State Information">

	/**
	 * Contains the logic to determine a draft's visibility.
	 */
	public function getVisibility() {
		return ($this->draft_password != '' ? "locked" : "unlocked");
	}
	
	/**
	 * Determines if the draft is completed
	 * @return bool true if the draft is completed, false otherwise
	 */
	public function isCompleted() {
		return $this->draft_status == "complete";
	}

	/**
	 * Determines if draft is undrafted
	 * @return bool true if the draft is undrafted, false otherwise
	 */
	public function isUndrafted() {
		return $this->draft_status == "undrafted";
	}

	/**
	 * Determines if draft is in progress
	 * @return bool true fi the draft is in progress, false otherwise 
	 */
	public function isInProgress() {
		return $this->draft_status == "in_progress";
	}

	/**
	 * Determines if draft is password-protected
	 * @return bool true if the draft is password-protected, false otherwise
	 */
	public function isPasswordProtected() {
		return (isset($this->draft_password) && strlen($this->draft_password) > 0);
	}

	/**
	 * Determines if the draft style is serpentine
	 * @return bool true if the draft is serpentine style, false otherwise
	 */
	public function styleIsSerpentine() {
		return $this->draft_style == "serpentine";
	}

	/**
	 * Determines if the draft style is standard
	 * @return bool true if the draft is standard style, false otherwise
	 */
	public function styleIsStandard() {
		return $this->draft_sty == "standard";
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="Private Object Helpers">
	private static function fillDraftObject($mysql_array, $draft_id = 0) {
		$draft = new draft_object();
		
		if($draft_id > 0)
			$draft->draft_id = (int)$draft_id;
		else
			$draft->draft_id = (int)$mysql_array['draft_id'];
		
		$draft->draft_name = $mysql_array['draft_name'];
		$draft->draft_sport = $mysql_array['draft_sport'];
		$draft->draft_status = $mysql_array['draft_status'];
		$draft->draft_style = $mysql_array['draft_style'];
		$draft->draft_rounds = $mysql_array['draft_rounds'];
		$draft->draft_password = $mysql_array['draft_password'];
		$draft->draft_start_time = $mysql_array['draft_start_time'];
		$draft->draft_end_time = $mysql_array['draft_end_time'];
		$draft->draft_current_round = (int)$mysql_array['draft_current_round'];
		$draft->draft_current_pick = (int)$mysql_array['draft_current_pick'];
		
		return $draft;
	}
	// </editor-fold>
}

?>
