<?php

/**
 * PHP Draft search object for the public search function
 */
class search_object {
	public $keywords;
	public $team;
	public $position;
	private $usedConstruct;
	public $player_results;
	public $search_count;
	
	public function __construct($keywords, $team, $position) {
		$this->keywords = trim($keywords);
		$this->team = trim($team);
		$this->position = trim($position);
		$this->usedConstruct = true;
		$this->search_count = 0;
	}
	
	public function searchDraft($draft_id) {
		if(!$this->usedConstruct) {
			echo "Must use constructor.";
			throw new Exception;
			exit(1);
		}
		
		require_once("/models/player_object.php");
		$hasName = $this->hasName();
		
		if($this->hasName())
			player_object::searchPlayersByStrictCriteria($this, $draft_id);
		
		if($this->search_count == 0) {
			$this->emptyResultsData();
			player_object::searchPlayersByLooseCriteria($this, $draft_id);
		}
	}
	
	private function emptyResultsData() {
		unset($this->player_results);
		$this->search_count = 0;
	}
	
	// <editor-fold defaultstate="collapsed" desc="State Information">
	public function hasName() {
		return isset($this->keywords) && strlen($this->keywords) > 0;
	}
	
	public function hasTeam() {
		return isset($this->team) && strlen($this->team) > 0;
	}
	
	public function hasPosition() {
		return isset($this->position) && strlen($this->position) > 0;
	}
	// </editor-fold>
}
?>
