<?php
/**
 * Draft Service - an object manager for the PHPDraft "draft" object, which is the parent object.
 * 
 * A draft has many managers, and managers possess many players/picks.
 */

class draft_service {
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
	 * @return boolean success whether or not the MySQL transaction succeeded.
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
				(draft_id, draft_name, draft_sport, draft_status, draft_style, draft_rounds) 
				VALUES 
				(NULL, ?, ?, 'undrafted', ?, ?)");
			
			$insert_stmt->bindParam(1, $draft->draft_name);
			$insert_stmt->bindParam(2, $draft->draft_sport);
			$insert_stmt->bindParam(3, $draft->draft_style);
			$insert_stmt->bindParam(4, $draft->draft_rounds);
			
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
	 * @return boolean Success
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
	 * @return boolean Success
	 */
	public function updateStatus($draft, $new_status) {
		if($draft->isCompleted())
			return false;

		$was_undrafted = $draft->isUndrafted();

		$draft->draft_status = $new_status;
		$draft->draft_current_pick = 1;
		$draft->draft_current_round = 1;

		$draftJustStarted = $was_undrafted && $draft->isInProgress() ? true : false;

		if($draftJustStarted)
			$draft->draft_start_time = $draft->beginStartTime();
		else
			$draft->draft_start_time = "NULL";
		
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
		
		$erasePlayersSuccess = player_object::deletePlayersByDraft($draft->draft_id);

		if($erasePlayersSuccess === false) {
			throw new Exception("Unable to update draft status - unable to erase players.");
		}

		if($draftJustStarted) {
			$setupSuccess = $draft->setupPicks();

			if(!$setupSuccess) {
				throw new Exception("Unable to update draft status - unable to setup picks.");
			}
		}

		return $draft;
	}
}
?>
