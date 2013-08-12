<?php
/**
 * Draft Service - an object service for the PHPDraft "draft" object, which is the parent object.
 * 
 * A draft has many managers, and managers possess many players/picks.
 */

class draft_service {
	/*
	 * Load a given draft
	 * @return draft_object $draft if successful, exception thrown otherwise
	 */
	public function loadDraft($id = 0) {
		global $DBH; /* @var $DBH PDO */
		$id = (int)$id;
		$draft = new draft_object();
		
		if($id == 0)
			return $draft;
		
		$draft_stmt = $DBH->prepare("SELECT * FROM draft WHERE draft_id = ? LIMIT 1");
		$draft_stmt->setFetchMode(PDO::FETCH_INTO, $draft);
		$draft_stmt->bindParam(1, $id);
		
		if(!$draft_stmt->execute())
			throw new Exception("Unable to load draft id #" . $id);
		
		if(!$draft_stmt->fetch())
			throw new Exception("Unable to load draft id #" . $id);

		return $draft;
	}
	
	/**
	 * Check the validity of parent draft object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity($draft) {
		$errors = array();

		if(!isset($draft->draft_name) || strlen($draft->draft_name) == 0)
			$errors[] = "Draft Name is empty.";
		if(!isset($draft->draft_name) || strlen($draft->draft_sport) == 0)
			$errors[] = "Draft Sport is empty.";
		if(!isset($draft->draft_style) || strlen($draft->draft_style) == 0)
			$errors[] = "Draft Style is empty.";

		if($draft->draft_rounds < 1)
			$errors[] = "Draft rounds must be at least 1 or more.";

		if(empty($draft->draft_id) || $draft->draft_id == 0) {
			global $DBH; /* @var $DBH PDO */
			
			$name_stmt = $DBH->prepare("SELECT COUNT(draft_id) as count FROM draft where draft_name = ? AND draft_sport = ?");
			$name_stmt->bindParam(1, $draft->draft_name);
			$name_stmt->bindParam(2, $draft->draft_sport);
			
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
	 * Adds a new instance of this draft to the database
	 * @return draft_object $draft if successful, exception thrown otherwise
	 * TODO: Update this so we so something similar to a Save-or-update, either an INSERT or an UPDATE
	 */
	public function saveDraft($draft) {
		global $DBH; /* @var $DBH PDO */
		if($draft->draft_id > 0) {
			$update_stmt = $DBH->prepare("UPDATE draft 
				SET draft_name = ?, draft_sport = ?, draft_status = ?, draft_style = ?,
					draft_rounds = ?, draft_current_round = ?, draft_current_pick = ?, 
					draft_password = ? 
				WHERE draft_id = ?");
			
			$update_stmt->bindParam(1, $draft->draft_name);
			$update_stmt->bindParam(2, $draft->draft_sport);
			$update_stmt->bindParam(3, $draft->draft_status);
			$update_stmt->bindParam(4, $draft->draft_style);
			$update_stmt->bindParam(5, $draft->draft_rounds);
			$update_stmt->bindParam(6, $draft->draft_current_round);
			$update_stmt->bindParam(7, $draft->draft_current_pick);
			$update_stmt->bindParam(8, $draft->draft_password);
			$update_stmt->bindParam(9, $draft->draft_id);
			
			$result = $update_stmt->execute();
			//return $result;
			if($result == false) {
				throw new Exception("Unable to update draft.");
			}
			
			return $draft;
		}else {
			$insert_stmt = $DBH->prepare("INSERT INTO draft 
				(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds, draft_create_time) 
				VALUES 
				(NULL, ?, ?, 'undrafted', ?, ?, ?)");
			
			$insert_stmt->bindParam(1, $draft->draft_name);
			$insert_stmt->bindParam(2, $draft->draft_sport);
			$insert_stmt->bindParam(3, $draft->draft_style);
			$insert_stmt->bindParam(4, $draft->draft_rounds);
			$insert_stmt->bindParam(5, php_draft_library::getNowPhpTime());
			
			if(!$insert_stmt->execute()) {
				throw new Exception("Unable to save draft.");
			}
			
			$draft->draft_id = (int)$DBH->lastInsertId();		

			return $draft;
		}
	}
	
	/**
	 * Shifts all draft counters forward to the next pick
	 * @param player_object $next_pick
	 * @return draft_object $draft if successful, exception thrown otherwise
	 */
	public function moveDraftForward($draft, $next_pick) {
		global $DBH; /* @var $DBH PDO */
		if($next_pick !== false) {
			$draft->draft_current_pick = (int)$next_pick->player_pick;
			$draft->draft_current_round = (int)$next_pick->player_round;
			
			$stmt = $DBH->prepare("UPDATE draft SET draft_current_pick = ?, draft_current_round = ? WHERE draft_id = ?");
			$stmt->bindParam(1, $draft->draft_current_pick);
			$stmt->bindParam(2, $draft->draft_current_round);
			$stmt->bindParam(3, $draft->draft_id);
			
			if(!$stmt->execute()) {
				throw new Exception("Unable to move draft forward.");
			}
			
			return $draft;
		}else {
			$draft->draft_status = 'complete';
			$stmt = $DBH->prepare("UPDATE draft SET draft_status = ?, draft_end_time = ? WHERE draft_id = ?");
			$stmt->bindParam(1, $draft->draft_status);
			$stmt->bindParam(2, php_draft_library::getNowPhpTime());
			$stmt->bindParam(3, $draft->draft_id);
			
			if(!$stmt->execute()) {
				throw new Exception("Unable to move draft forward.");
			}
			
			return $draft;
		}
	}
	
	/**
	 * Move the draft into drafting or undrafted status
	 * @param string $new_status
	 * @return draft_object $draft if successful, exception thrown otherwise
	 */
	public function updateStatus($draft, $new_status) {
		if($draft->isCompleted())
			return false;
		
		$PLAYER_SERVICE = new player_service();

		$draft->draft_status = $new_status;
		$draft->draft_current_pick = 1;
		$draft->draft_current_round = 1;

		$draftJustStarted = $draft->isUndrafted() && $draft->isInProgress() ? true : false;

		try {
			$draft->draft_start_time = $this->getDraftStartTime($draft, $draftJustStarted);
		}catch(Exception $e) {
			throw new Exception("Unable to update draft status: " . $e->getMessage());
		}
		
		try {
			$this->saveDraft($draft);
		}catch(Exception $e) {
			throw new Exception("Unable to update draft status - unable to save draft.");
		}

		//Were either going from UNDRAFTED to IN PROGRESS, or vice versa, cannot move
		//out of "COMPLETED", so either way we want to wipe clean any trades & players.
		$eraseTradesSuccess = trade_object::DeleteTradesByDraft($draft->draft_id);
		
		if($eraseTradesSuccess === false) {
			throw new Exception("Unable to update draft status - unable to erase trades.");
		}
		
		try {
			$PLAYER_SERVICE->deletePlayersByDraft($draft->draft_id);
		}catch(Exception $e) {
			throw new Exception("Unable to update draft status: " . $e->getMessage());
		}

		if($draftJustStarted) {
			try {
				$this->setupPicks($draft);
			}catch(Exception $e) {
				throw new Exception("Unable to update draft status - unable to setup picks.");
			}
		}

		return $draft;
	}
	
	/**
	 * Goes through and creates all of the draft's picks as placeholders, triggered when the draft status is set to "in progress"
	 * @return draft_object $draft if successful, exception thrown otherwise
	 */
	public function setupPicks($draft) {
		$pick = 1;
		$even = true;
		$MANAGER_SERVICE = new manager_service();
		$PLAYER_SERVICE = new player_service();
		
		for($current_round = 1; $current_round <= $draft->draft_rounds; $current_round++) {
			if($draft->styleIsSerpentine()) {
				if($even) {
					$managers = $MANAGER_SERVICE->getManagersByDraft($draft->draft_id, true);
					$even = false;
				} else {
					$managers = $MANAGER_SERVICE->getManagersByDraft($draft->draft_id, true, "DESC");
					$even = true;
				}
			}else
				$managers = $MANAGER_SERVICE->getManagersByDraft($draft->draft_id, true);
			
			if(count($managers) == 0) {
				throw new Exception("Unable to setup picks - unable to get managers.");
			}

			foreach($managers as $manager) {
				$new_pick = new player_object();
				$new_pick->manager_id = $manager->manager_id;
				$new_pick->draft_id = $draft->draft_id;
				$new_pick->player_round = $current_round;
				$new_pick->player_pick = $pick;
				
				//FIX: Why are picks not being created here? No exception thrown, but rows arent being created
				try{
					$PLAYER_SERVICE->savePlayer($new_pick);
				}catch(Exception $e) {
					throw new Exception("Unable to save player: " . $e->getMessage());
				}

				$pick++;
			}
		}
		return $draft;
	}
	
	/**
	 * Loads all draft picks and sorts them for suitable display
	 * @return array 
	 */
	public function getAllDraftPicks($draft) {
		$picks = array();
		
		$PLAYER_SERVICE = new player_service();
		
		$sort = true;
		for($i = 1; $i <= $draft->draft_rounds; ++$i) {
			if($draft->styleIsSerpentine()) {
				$picks[] = $PLAYER_SERVICE->getAllPlayersByRound($draft->draft_id, $i, $sort);
				$sort = $sort ? false : true;
			}else{
				$picks[] = $PLAYER_SERVICE->getAllPlayersByRound($draft->draft_id, $i);
			}
		}
		
		return $picks;
	}
	
	/**
	 * Set the draft's start time to NOW in database and return that value.
	 * @param bool $draftJustStarted If the draft status has just changed from "undrafted" to "in progress"
	 * @return string timestamp given to draft
	 */
	public function getDraftStartTime($draft, $draftJustStarted) {
		global $DBH; /* @var $DBH PDO */
		$draft_start_time = $draftJustStarted ? php_draft_library::getNowPhpTime() : "NULL";
		
		$update_statement = $DBH->prepare("UPDATE draft set draft_start_time = ? WHERE draft_id = ? LIMIT 1");
		$update_statement->bindParam(1, $draft_start_time);
		$update_statement->bindParam(2, $draft->draft_id);
		
		if(!$update_statement->execute()) {
			throw new Exception("Unable to set draft start time.");
		}
		
		return $draft_start_time;
	}
	
	/**
	 * Removes a draft, all of its managers and all of their picks permanently (hard delete)
	 * @return bool on success, exception thrown otherwise
	 */
	public function deleteDraft($draft) {
		if($draft->draft_id == 0) {
			throw new Exception("Draft does not exist in database.");
		}
		
		$MANAGER_SERVICE = new manager_Service();
		$PLAYER_SERVICE = new player_service();
		
		$tradeRemovalSuccess = trade_object::DeleteTradesByDraft($draft->draft_id);
		
		if($tradeRemovalSuccess === false) {
			throw new Exception("Unable to delete trades belonging to draft.");
		}
		
		try {
			$PLAYER_SERVICE->deletePlayersByDraft($draft->draft_id);
		}catch(Exception $e) {
			throw new Exception("Unable to delete picks belonging to draft.");
		}
		
		try {
			$MANAGER_SERVICE->deleteManagersByDraft($draft->draft_id);
		}catch(Exception $e) {
			throw new Exception("Unable to delete managers belonging to draft.");
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$sql = "DELETE FROM draft WHERE draft_id = " . (int)$draft->draft_id . " LIMIT 1";
		
		if(!$DBH->exec($sql)) {
			throw new Exception("Unexpected error while attempting to delete draft.");
		}
		
		return true;
	}
	
	/**
	 * Returns an array of all current drafts in the database
	 * @return array of all available draft objects
	 */
	public function getAllDrafts() {
		$drafts = array();
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM draft ORDER BY draft_create_time");
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'draft_object');
		$stmt->execute();
		
		while($draft_row = $stmt->fetch())
			$drafts[] = $draft_row;

		return $drafts;
	}
}
?>
