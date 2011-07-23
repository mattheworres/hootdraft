<?php

require_once("php_draft_library.php");

/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 *
 * @property int $draft_id The unique identifier for this draft
 * @property string $draft_name The string identifier for this draft
 * @property string $draft_status The description of the status is in
 * @property string $visibility Either 'locked' or 'unlocked' depending on existence of a password
 * @property string $draft_password Determines draft visibility
 * @property string $draft_sport
 * @property string $draft_style Either 'serpentine' or 'standard'
 * @property int $draft_rounds Number of rounds draft will have in total
 * @property string $start_time Timestamp of when the draft was put into "started" status
 * @property string $end_time Timestamp of when the draft was put into "completed" status
 * @property int $current_round
 * @property int $current_pick
 */
class draft_object {

	public $draft_id;
	public $draft_name;
	public $draft_status;
	public $visibility;
	public $draft_password;
	public $draft_sport;
	public $draft_style;
	public $draft_rounds;
	public $start_time;
	public $end_time;
	public $current_round;
	public $current_pick;

	public function __construct($id = 0) {
		if(intval($id) == 0)
			return false;
		
		$id = intval($id);

		$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = " . $id . " LIMIT 1");

		if(!$draft_row = mysql_fetch_array($draft_result))
			return false;

		$this->draft_id = intval($draft_row['draft_id']);
		$this->draft_name = $draft_row['draft_name'];
		$this->draft_sport = $draft_row['draft_sport'];
		$this->draft_status = $draft_row['draft_status'];
		$this->draft_style = $draft_row['draft_style'];
		$this->draft_rounds = $draft_row['draft_rounds'];
		$this->draft_password = $draft_row['draft_password'];
		//TODO: Figure out how to convert to useable PHP datetimes:
		//NOTE: Using strtotime, in the first draft page that's what I was using... update if necessary.
		$this->start_time = strtotime($draft_row['draft_start_time']);
		$this->end_time = strtotime($draft_row['draft_end_time']);
		$this->current_round = intval($draft_row['draft_current_round']);
		$this->current_pick = intval($draft_row['draft_current_pick']);

		return true;
	}

	/**
	 * Check the validity of parent draft object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity() {
		$errors = array();

		if(empty($this->draft_name))
			$errors[] = "Draft Name is empty.";
		if(empty($this->draft_sport))
			$errors[] = "Draft Sport is empty.";
		if(empty($this->draft_style))
			$errors[] = "Draft Style is empty.";

		if($this->draft_rounds < 1)
			$errors[] = "Draft rounds must be at least 1 or more.";

		$name_count = mysql_num_rows(mysql_query("SELECT draft_id FROM draft WHERE draft_name = '" . $this->draft_name . "' AND draft_sport = '" . $this->draft_sport . "'"));

		if($name_count > 0)
			$errors[] = "Draft already found with that name and sport.";

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
	 * Adds a new instance of this draft to the database
	 * @return boolean success whether or not the MySQL transaction succeeded.
	 * TODO: Update this so we so something similar to a Save-or-update, either an INSERT or an UPDATE
	 */
	public function saveDraft() {
		if($this->draft_id > 0) {
			$sql = "UPDATE draft SET " .
				"draft_name = '" . mysql_real_escape_string($this->draft_name) . "', " .
				"draft_sport = '" . mysql_real_escape_string($this->draft_sport) . "', " .
				"draft_status = '" . mysql_real_escape_string($this->draft_status) . "', " .
				"draft_style = '" . mysql_real_escape_string($this->draft_style) . "', " .
				"draft_rounds = '" . intval($this->draft_rounds) . "', ";

			if(intval($this->current_round) > 1)
				$sql .= "draft_current_round = " . intval($this->current_round) . ", ";
			if(intval($this->current_pick) > 1)
				$sql .= "draft_current_pick = " . intval($this->current_pick) . ", ";

			$sql .= "draft_password = '" . mysql_real_escape_string($this->draft_password) . "' " .
				"WHERE draft_id = " . intval($this->draft_id);

			return mysql_query($sql);
		}else {
			$sql = "INSERT INTO draft "
				. "(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds) "
				. "VALUES "
				. "(NULL, '" . $this->draft_name . "', '" . $this->draft_sport . "', 'undrafted', '" . $this->draft_style . "', " . $this->draft_rounds . ")";

			if(!mysql_query($sql))
				return false;

			$this->draft_id = mysql_insert_id();

			return true;
		}
	}

	public function updateStatus($new_status) {
		$this->draft_status = $new_status;
		$saveSuccess = $this->saveDraft();

		if(!$saveSuccess)
			return false;
	}

	/**
	 * Check to ensure $status is in the correct state to prevent any borking of the database.
	 * @return bool 
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
			$draft->draft_id = $draft_row['draft_id'];
			$draft->draft_name = $draft_row['draft_name'];
			$draft->draft_status = $draft_row['draft_status'];
			$draft->visibility = ($draft_row['draft_password'] != '' ? "locked" : "unlocked");
			$draft->draft_password = $draft_row['draft_password'];
			$draft->draft_sport = $draft_row['draft_sport'];
			$draft->draft_style = $draft_row['draft_style'];
			$draft->draft_rounds = intval($draft_row['draft_rounds']);
			$draft->start_time = $draft_row['start_time'];
			$draft->end_time = $draft_row['end_time'];
			$draft->current_round = intval($draft_row['current_round']);
			$draft->current_pick = intval($draft_row['current_pick']);
			$drafts[] = $draft;
		}

		return $drafts;
	}

	/**
	 * Returns a string representation of the time span of this draft
	 * @return string String representation of the duration of this draft
	 */
	public function getDraftDuration() {
		if($this->draft_status == "complete")
			return secondsToWords($this->start_time - $this->end_time);
		else
			return "";
	}
	// <editor-fold defaultstate="collapsed" desc="Draft State Information">

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
	// </editor-fold>
}

?>
