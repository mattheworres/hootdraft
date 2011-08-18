<?php

/**
 * Represents a PHPDraft "manager" object.
 *
 * Managers have many players (picks), and belong to a single draft.
 *
 * @property int $manager_id The unique identifier for this manager
 * @property int $draft_id Foreign key to the draft this manager belongs to
 * @property string $manager_name Textual display name for each manager
 * @property string $manager_email Email address of manager
 * @property int $draft_order The order in which the manager makes a pick in the draft.
 */

class manager_object {

	public $manager_id;
	public $draft_id;
	public $manager_name;
	public $manager_email;
	public $draft_order;

	public function __construct($manager_id = 0) {
		if((int)$manager_id == 0)
			return false;
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM managers WHERE manager_id = ? LIMIT 1");
		$stmt->bindParam(1, $manager_id);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return false;
		
		if(!$stmt->fetch())
			return false;
		
		return true;
	}

	/**
	 * Check the validity of parent manager object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity() {
		$email_regex = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";
		
		$errors = array();

		if(!isset($this->manager_name) || strlen($this->manager_name) == 0)
			$errors[] = "Manager name is empty.";
		
		
		if(isset($this->manager_email) && strlen($this->manager_email) > 0) {
			$is_valid_email = (bool)preg_match($email_regex, $this->manager_email);
			if(!$is_valid_email)
				$errors[] = "Manager email is not in the correct format";
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$has_draft_stmt = $DBH->prepare("SELECT COUNT(draft_id) as count FROM draft WHERE draft_id = ?");
		$has_draft_stmt->bindParam(1, $this->draft_id);
		
		if(!$has_draft_stmt->execute())
			$errors[] = $this->draft_name . " unable to be added";
		
		if(!$row = $has_draft_stmt->fetch())
			$errors[] = $this->draft_name . " unable to be added";
		
		if((int)$row['count'] == 0)
			$errors[] = "Manager's draft doesn't exist.";
		
		return $errors;
	}

	/**
	 * Move the given manager up in their draft order
	 * @param int $manager_id Id of the manager to move up in the draft order
	 * @return bool success on whether the move was completed successfully 
	 */
	public function moveManagerUp() {
		global $DBH; /* @var $DBH PDO */
		
		if($this->draft_order == 0)
			return false;

		$old_place = $this->draft_order;

		if($old_place == 1)
			return true;

		$new_place = $old_place - 1;
		
		$swap_mgr_stmt = $DBH->prepare("SELECT manager_id FROM managers WHERE draft_id = ? AND manager_id != ? AND draft_order = ?");
		$swap_mgr_stmt->bindParam(1, $this->draft_id);
		$swap_mgr_stmt->bindParam(2, $this->manager_id);
		$swap_mgr_stmt->bindParam(3, $new_place);
		
		if(!$swap_mgr_stmt->execute())
			return false;
		
		if(!$swap_manager_row = $swap_mgr_stmt->fetch())
			return false;

		$swap_manager_id = (int)$swap_manager_row['manager_id'];
		
		$swap_stmt = $DBH->prepare("UPDATE managers SET draft_order = ? WHERE draft_id = ? AND manager_id = ?");
		$swap_stmt->bindParam(1, $draft_order);
		$swap_stmt->bindParam(2, $this->draft_id);
		$swap_stmt->bindParam(3, $manager_id);
		
		$draft_order = $new_place;
		$manager_id = $this->manager_id;
		
		$swap_1_success = $swap_stmt->execute();
		
		$draft_order = $old_place;
		$manager_id = $swap_manager_id;
		
		$swap_2_success = $swap_stmt->execute();

		if(!$swap_1_success || !$swap_2_success)
			return false;

		return true;
	}

	/**
	 * Move the given manager down in their draft order
	 * @param int $manager_id Id of the manager to move up in the draft order
	 * @return bool Success on whether the move was completed successfully 
	 */
	public function moveManagerDown() {
		global $DBH; /* @var $DBH PDO */
		
		if($this->draft_order == 0)
			return false;

		$old_place = $this->draft_order;
		
		$lowest_order_stmt = $DBH->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
		$lowest_order_stmt->bindParam(1, $this->draft_id);
		
		if(!$lowest_order_stmt->execute())
			return false;
		
		if(!$lowest_order_row = $lowest_order_stmt->fetch())
			return false;
		
		$lowest_order = (int)$lowest_order_row['draft_order'];

		if($old_place == $lowest_order)
			return true;

		$new_place = $old_place + 1;
		
		$swap_mgr_stmt = $DBH->prepare("SELECT draft_order, manager_id FROM managers WHERE draft_id = ? AND manager_id != ? AND draft_order = ?");
		$swap_mgr_stmt->bindParam(1, $this->draft_id);
		$swap_mgr_stmt->bindParam(2, $this->manager_id);
		$swap_mgr_stmt->bindParam(3, $new_place);
		
		if(!$swap_mgr_stmt->execute())
			return false;
		
		if(!$swap_mgr_row = $swap_mgr_stmt->fetch())
			return false;

		$swap_manager_id = (int)$swap_mgr_row['manager_id'];
		
		$swap_stmt = $DBH->prepare("UPDATE managers SET draft_order = ? WHERE draft_id = ? AND manager_id = ?");
		$swap_stmt->bindParam(1, $draft_order);
		$swap_stmt->bindParam(2, $this->draft_id);
		$swap_stmt->bindParam(3, $manager_id);
		
		$draft_order = $new_place;
		$manager_id = $this->manager_id;
		
		$swap_1_success = $swap_stmt->execute();
		
		$draft_order = $old_place;
		$manager_id = $swap_manager_id;
		
		$swap_2_success = $swap_stmt->execute();
		
		if(!$swap_1_success || !$swap_2_success)
			return false;

		return true;
	}

	/**
	 * Saves or updates the manager object
	 * @return bool Success of the operation, be it an insert or update.
	 */
	public function saveManager() {
		global $DBH; /* @var $DBH PDO */
		if($this->manager_id > 0) {
			$update_stmt = $DBH->prepare("UPDATE managers SET manager_name = ?, manager_email = ? WHERE manager_id = ? AND draft_id = ?");
			$update_stmt->bindParam(1, $this->manager_name);
			$update_stmt->bindParam(2, $this->manager_email);
			$update_stmt->bindParam(3, $this->manager_id);
			$update_stmt->bindParam(4, $this->draft_id);
			
			return $update_stmt->execute();
		} elseif($this->draft_id > 0) {
			$save_stmt = $DBH->prepare("INSERT INTO managers (manager_id, draft_id, manager_name, manager_email, draft_order) VALUES (?, ?, ?, ?, ?)");
			$save_stmt->bindParam(1, $this->manager_id);
			$save_stmt->bindParam(2, $this->draft_id);
			$save_stmt->bindParam(3, $this->manager_name);
			$save_stmt->bindParam(4, $this->manager_email);
			$save_stmt->bindParam(5, $new_draft_order);
			
			$new_draft_order = (int)($this->getLowestDraftorder() + 1);
			
			if(!$save_stmt->execute())
				return false;

			$this->manager_id = (int)$DBH->lastInsertId();

			return true;
		}else
			return false;
	}

	/**
	 * In order to get the lowest current draft number for this manager's draft.
	 * @return int Lowest draft order for the given draft 
	 */
	public function getLowestDraftorder() {
		global $DBH; /* @var $DBH PDO */
		$stmt = $DBH->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
		$stmt->bindParam(1, $this->draft_id);
		$stmt->execute();
		$row = $stmt->fetch();
		
		return (int)$row['draft_order'];
	}

	public function deleteManager() {
		$draft = new draft_object($this->draft_id);

		if($draft->draft_id == 0 || !$draft->isUndrafted()) {
			return false;
		}

		$old_order = $this->draft_order;
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("DELETE FROM managers WHERE manager_id = ? AND draft_id = ? LIMIT 1");
		$stmt->bindParam(1, $this->manager_id);
		$stmt->bindParam(2, $this->draft_id);
		
		if(!$stmt->execute())
			return false;

		$success = $this->cascadeNewDraftOrder($old_order);

		return $success;
	}
	
	public static function deleteManagersByDraft($draft_id) {
		$draft_id = (int)$draft_id;

		if($draft_id == 0)
			return false;

		$managers = manager_object::getManagersByDraft($draft_id);

		$id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.	

		foreach($managers as $manager) {
			$id_string .= "," . $manager->manager_id;
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("DELETE FROM managers WHERE manager_id IN (?)");
		$stmt->bindParam(1, $id_string);
		
		$success = $stmt->execute();
		
		return $success;
	}

	/**
	 * Given a single draft ID, get all managers for that draft
	 * @param int $draft_id
	 * @param bool $draft_order_sort Whether or not to sort by the manager's order in the draft. If false, manager_name is used
	 * @return array of manager objects
	 */
	public static function getManagersByDraft($draft_id, $draft_order_sort = false, $order_sort = "ASC") {
		$managers = array();
		$sql = "SELECT * FROM managers WHERE draft_id = '" . $draft_id . "' ORDER BY ";
		$sql .= $draft_order_sort ? "draft_order" : "manager_name";

		$managers_result = mysql_query($sql);

		while($manager_row = mysql_fetch_array($managers_result)) {
			$new_manager = new manager_object();
			$new_manager->manager_id = (int)$manager_row['manager_id'];
			$new_manager->draft_id = (int)$manager_row['draft_id'];
			$new_manager->manager_name = $manager_row['manager_name'];
			$new_manager->manager_email = $manager_row['manager_email'];
			$new_manager->draft_order = (int)$manager_row['draft_order'];
			$managers[] = $new_manager;
		}

		return $managers;
	}

	/**
	 * Given a single draft ID, get the number of managers currently connected to that draft.
	 * @param int $draft_id
	 * @return int $number_of_managers
	 */
	public static function getCountOfManagersByDraft($draft_id) {
		$sql = "SELECT manager_id FROM managers WHERE draft_id = " . $draft_id . " ORDER BY manager_name";

		return mysql_num_rows(mysql_query($sql));
	}

	/**
	 * After a manager is deleted, all managers that are after him have their draft order bumped up by one all the way down, and this function does that
	 * @param int $old_order The order of the manager being deleted
	 * @return boolean success
	 */
	private function cascadeNewDraftOrder($old_order) {
		$sql = "SELECT manager_id FROM managers WHERE manager_id != " . $this->manager_id . " AND draft_id = " . $this->draft_id . " AND draft_order > " . $old_order . " ORDER BY draft_order ASC";
		$managers_result = mysql_query($sql);

		$success = true;

		while($manager_row = mysql_fetch_array($managers_result)) {
			$inner_sql = "UPDATE managers SET draft_order = " . $old_order . " WHERE manager_id = " . (int)$manager_row['manager_id'];
			if(!mysql_query($inner_sql))
				$success = false;
			$old_order++;
		}

		return $success;
	}
}

?>
