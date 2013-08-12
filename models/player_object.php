<?php

/**
 * Represents a PHPDraft player, or "pick" in the draft.
 * 
 * Each player is owned by a manager, who belongs to a draft.
 * 
 * Players carry draft information on them - such as which round and which pick
 * they exist at (player information will be blank if they are unchecked)
 */
class player_object {
	/** @var int */
	public $player_id;
	/** @var int The ID of the manager this player belongs to */
	public $manager_id;
	/** @var int The ID of the draft this player belongs to */
	public $draft_id;
	/** @var string */
	public $first_name;
	/** @var string */
	public $last_name;
	/** @var string The professional team the player plays for. Stored as three character abbreviation */
	public $team;
	/** @var string The position the player plays. Stored as one to three character abbreviation. */
	public $position;
	/** @var string Timestamp of when the player was picked. Use strtotime to convert for comparisons, NULL for undrafted */
	public $pick_time;
	/** @var int Amount of seconds that were consumed during this pick since previous pick */
	public $pick_duration;
	/** @var int Round the player was selected in */
	public $player_round;
	/** @var int Pick the player was selected at */
	public $player_pick;
	/** @var string Name of the manager that made the pick. NOTE: Only available on selected picks, and is kind've a cheat. */
	public $manager_name;
	/** @var int */
	public $search_score;
	
	public function __construct() {
		//Leaving this here in case further init needs to occur
	}
	
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
	
	/**
	 * Determine whether a player/pick has been selected. Generally determines if it is editable.
	 * @return boolean true if selected 
	 */
	public function hasBeenSelected() {
		return isset($this->pick_time) && isset($this->pick_duration)
			&& strlen($this->pick_time) > 0 && $this->pick_duration > 0;
	}
}
?>