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
		
		if($this->hasName())
			$this->searchPlayersByStrictCriteria($draft_id);
		
		if($this->search_count == 0) {
			$this->emptyResultsData();
			$this->searchPlayersByLooseCriteria($draft_id);
		}
	}
	
	/**
	 * Searches for picked players with strict criteria, using the MATCH() and score method. Sorts by score ASC first, then pick DESC last.
	 * @param int $draft_id 
	 */
	public function searchPlayersByStrictCriteria($draft_id) {
		global $DBH; /* @var $DBH PDO */
		$draft_id = (int)$draft_id;
		$param_number = 4;
		$players = array();
		
		$sql = "SELECT p.*, m.manager_name, MATCH (p.first_name, p.last_name) AGAINST (?) as search_score ".
			"FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE MATCH (p.first_name, p.last_name) AGAINST (?) AND p.draft_id = ? ";
		
		if($this->hasTeam())
			$sql .= "AND p.team = ? ";
		
		if($this->hasPosition())
			$sql .= "AND p.position = ? ";
		
		$sql .= "AND p.pick_time IS NOT NULL ORDER BY search_score ASC, p.player_pick DESC";
		
		$stmt = $DBH->prepare($sql);
		$stmt->bindParam(1, $this->keywords);
		$stmt->bindParam(2, $this->keywords);
		$stmt->bindParam(3, $draft_id);
		if($this->hasTeam()) {
			$stmt->bindParam(4, $this->team);
			$param_number++;
		}
			
		if($this->hasPosition()) {
			$stmt->bindParam($param_number, $this->position);
			$param_number++;
		}
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		while($player = $stmt->fetch())
			$players[] = $player;
		
		$this->player_results = $players;
		$this->search_count = count($players);
	}
	
	/**
	 * Search picked players by a loose criteria that uses a LIKE %% query. Used if strict query returns 0 results. Sorts by pick DESC.
	 * @param int $draft_id 
	 */
	public function searchPlayersByLooseCriteria($draft_id) {
		global $DBH; /* @var $DBH PDO */
		$draft_id = (int)$draft_id;
		$players = array();
		$param_number = 2;
		$loose_search_score = -1;
		
		$sql = "SELECT p.*, m.manager_name FROM players p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ? ";
		
		if($this->hasName())	
			$sql .= "AND (p.first_name LIKE ? OR p.last_name LIKE ?)";
		
		if($this->hasTeam())
			$sql .= "AND p.team = ? ";
		
		if($this->hasPosition())
			$sql .= "AND p.position = ? ";
		
		$sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick DESC";
		
		$stmt = $DBH->prepare($sql);
		$stmt->bindParam(1, $draft_id);
		
		if($this->hasName()) {
			$stmt->bindParam($param_number, $keywords);
			$param_number++;
			$stmt->bindParam($param_number, $keywords);
			$param_number++;
			
			$keywords = "%" . $this->keywords . "%";
		}
		
		if($this->hasTeam()) {
			$stmt->bindParam($param_number, $this->team);
			$param_number++;
		}
		
		if($this->hasPosition()) {
			$stmt->bindParam($param_number, $this->position);
			$param_number++;
		}
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'player_object');
		
		if(!$stmt->execute())
			return false;
		
		while($player = $stmt->fetch()) {
			$player->search_score = $loose_search_score;
			$players[] = $player;
			
			$loose_search_score--;
		}
		
		$this->player_results = $players;
		$this->search_count = count($players);
	}
	
	/**
	 * Used if the strict search returns no results, empty the results (for any reason) and set the count to zero.
	 */
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
