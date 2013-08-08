<?php
/**
 * Manager Service - an object service for the PHPDraft "manager" object.
 * 
 * Managers have many players (picks), and belong to a single draft.
 */
class manager_service {
	/*
	 * Load a given manager
	 * @return manager_object $manager if successful, exception thrown otherwise
	 */
	public function loadManager($id = 0) {
		$manager = new manager_object();
		
		$id = (int)$id;
		
		if($id == 0) {
			return $manager;
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM managers WHERE manager_id = ? LIMIT 1");
		$stmt->bindParam(1, $id);
		$stmt->setFetchMode(PDO::FETCH_INTO, $manager);
		
		if(!$stmt->execute()) {
			throw new Exception("Unable to load manager.");
		}
		
		if(!$stmt->fetch()) {
			throw new Exception("Unable to load manager.");
		}
		
		return $manager;
	}
	
	/**
	 * Check the validity of parent manager object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity($manager) {
		$email_regex = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";
		
		$errors = array();

		if(!isset($manager->manager_name) || strlen($manager->manager_name) == 0)
			$errors[] = "Manager name is empty.";
		
		
		if(isset($manager->manager_email) && strlen($manager->manager_email) > 0) {
			$is_valid_email = (bool)preg_match($email_regex, $manager->manager_email);
			if(!$is_valid_email)
				$errors[] = "Manager email is not in the correct format";
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$has_draft_stmt = $DBH->prepare("SELECT COUNT(draft_id) as count FROM draft WHERE draft_id = ?");
		$has_draft_stmt->bindParam(1, $manager->draft_id);
		
		if(!$has_draft_stmt->execute())
			$errors[] = $manager->draft_name . " unable to be added";
		
		if(!$row = $has_draft_stmt->fetch())
			$errors[] = $manager->draft_name . " unable to be added";
		
		if((int)$row['count'] == 0)
			$errors[] = "Manager's draft doesn't exist.";
		
		return $errors;
	}
}
?>
