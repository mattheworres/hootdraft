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
		$stmt->bindParam(1, $manager_id);
		$stmt->setFetchMode(PDO::FETCH_INTO, $manager);
		
		if(!$stmt->execute()) {
			throw new Exception("Unable to load manager.");
		}
		
		if(!$stmt->fetch()) {
			throw new Exception("Unable to load manager.");
		}
		
		return $manager;
	}
}
?>
