<?php

/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 */
class draft_object {
	/** @var int $draft_id The unique identifier for this draft */
	public $draft_id;
	/** @var string $draft_name The string identifier for this draft */
	public $draft_name;
	/** @var string $draft_sport */
	public $draft_sport;
	/** @var string $draft_status The description of the status is in */
	public $draft_status;
	/** @var string $draft_style Either 'serpentine' or 'standard' */
	public $draft_style;
	/** @var int $draft_rounds Number of rounds draft will have in total */
	public $draft_rounds;
	/** @var string $draft_password Determines draft visibility */
	public $draft_password;
	/** @var string $draft_start_time datetime of when the draft was put into "started" status */
	public $draft_start_time;
	/** @var string $draft_end_time datetime of when the draft was put into "completed" status */
	public $draft_end_time;
	/** @var int $draft_current_round */
	public $draft_current_round;
	/** @var int $draft_current_pick */
	public $draft_current_pick;
	/** @var array $sports_teams An array of all of the teams in the pro sport. Capitalized abbreviation is key, full name is value. */
	public $sports_teams;
	/** @var array $sports_positions An array of all the positions in the pro sport. Capitalized abbreviation is key, full name is value. */
	public $sports_positions;
	/** @var array $sports_colors An array of all the colors used for each position in the draft. Capitalized position abbreviation is key, hex color string is value (with # prepended) */
	public $sports_colors;

	public function __construct() {
		//Leaving this here in case any init is needed on new drafts.
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
	 * Goes through and creates all of the draft's picks as placeholders, triggered when the draft status is set to "in progress"
	 * @return bool $success True if successful 
	 */
	public function setupPicks() {
		$pick = 1;
		$even = true;
		
		for($current_round = 1; $current_round <= $this->draft_rounds; $current_round++) {
			if($this->styleIsSerpentine()) {
				if($even) {
					$managers = manager_object::getManagersByDraft($this->draft_id, true);
					$even = false;
				} else {
					$managers = manager_object::getManagersByDraft($this->draft_id, true, "DESC");
					$even = true;
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
	
	/**
	 * Loads all draft picks and sorts them for suitable display
	 * @return array 
	 */
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
		global $DBH; /* @var $DBH PDO */
		$draft_start_time = php_draft_library::getNowPhpTime();
		
		$update_statement = $DBH->prepare("UPDATE draft set draft_start_time = ? WHERE draft_id = ? LIMIT 1");
		$update_statement->bindParam(1, $draft_start_time);
		$update_statement->bindParam(2, $this->draft_id);
		
		$update_statement->execute();
		
		return $draft_start_time;
	}
	
	/**
	 * Removes a draft, all of its managers and all of their picks permanently (hard delete)
	 * @return bool Success
	 */
	public function deleteDraft() {
		if($this->draft_id == 0)
			return false;
		
		$tradeRemovalSuccess = trade_object::DeleteTradesByDraft($this->draft_id);
		
		if($tradeRemovalSuccess === false)
			return false;
		
		$pickRemovalSuccess = player_object::deletePlayersByDraft($this->draft_id);
		
		if($pickRemovalSuccess === false)
			return false;
		
		$managerRemovalSuccess = manager_object::deleteManagersByDraft($this->draft_id);
		
		if($managerRemovalSuccess === false)
			return false;
		
		global $DBH; /* @var $DBH PDO */
		
		$sql = "DELETE FROM draft WHERE draft_id = " . (int)$this->draft_id . " LIMIT 1";
		
		return $DBH->exec($sql);
	}
	
	/**
	 * Check whether or not current user is authenticated to view this draft.
	 * @return boolean 
	 */
	public function checkDraftPublicLogin() {
		if(!$this->isPasswordProtected())
			return true;
		
		return isset($_SESSION['did']) && isset($_SESSION['draft_password']) 
			&& $_SESSION['did'] == $this->draft_id 
			&& $_SESSION['draft_password'] == $this->draft_password;
	}
	
	/**
	 * Returns an array of the last five picks (player_object)
	 * @return player_object
	 */
	public function getLastTenPicks() {
		return player_object::getLastTenPicks($this->draft_id);
	}
	
	/**
	 * Returns an array of the last five picks (player_object)
	 * @return player_object 
	 */
	public function getLastFivePicks() {
		return player_object::getLastFivePicks($this);
	}
	
	/**
	 * Get the last player pick, or false on 0 rows.
	 * @return player_object
	 */
	public function getLastPick() {
		return player_object::getLastPick($this);
	}
	
	/**
	 * Returns the player_object that is the current pick for the draft.
	 * @return player_object
	 */
	public function getCurrentPick() {
		return player_object::getCurrentPick($this);
	}
	
	/**
	 * Get the next pick in the draft
	 * @return player_object 
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
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM draft ORDER BY draft_id");
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'draft_object');
		$stmt->execute();
		
		while($draft_row = $stmt->fetch())
			$drafts[] = $draft_row;

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
}
?>
