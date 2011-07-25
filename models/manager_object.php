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
		if(intval($manager_id) == 0)
			return false;

		$sql = "SELECT * FROM managers WHERE manager_id = " . $manager_id . " LIMIT 1";
		$manager_result = mysql_query($sql);
		if(!$manager_result)
			return false;

		$manager_row = mysql_fetch_array($manager_result);

		$this->manager_id = intval($manager_row['manager_id']);
		$this->draft_id = intval($manager_row['draft_id']);
		$this->manager_name = $manager_row['manager_name'];
		$this->manager_email = $manager_row['manager_email'];
		$this->draft_order = intval($manager_row['draft_order']);

		return true;
	}

	/**
	 * Check the validity of parent manager object and return array of error descriptions if invalid.
	 * @return array/string errors
	 */
	public function getValidity() {
		$errors = array();

		if(empty($this->manager_name))
			$errors[] = "Manager name is empty.";

		$has_a_draft = mysql_num_rows(mysql_query("SELECT draft_id FROM draft WHERE draft_id = " . $this->draft_id)) > 0;

		if(!$has_a_draft)
			$errors[] = "Manager's draft doesn't exist.";

		return $errors;
	}

	/**
	 * Move the given manager up in their draft order
	 * @param int $manager_id Id of the manager to move up in the draft order
	 * @return bool success on whether the move was completed successfully 
	 */
	public function moveManagerUp() {
		if($this->draft_order == 0)
			return false;

		$old_place = $this->draft_order;

		if($old_place == 1)
			return true;

		$new_place = $old_place - 1;

		$swap_manager_result = mysql_query("SELECT manager_id FROM managers WHERE draft_id = " . $this->draft_id . " AND manager_id != " . $this->manager_id . " AND draft_order = " . $new_place);

		if(!$swap_manager_result)
			return false;

		$swap_manager_row = mysql_fetch_array($swap_manager_result);

		$swap_manager_id = intval($swap_manager_row['manager_id']);

		$sql1 = "UPDATE managers SET draft_order = " . $new_place . " WHERE draft_id = " . $this->draft_id . " AND manager_id = " . $this->manager_id;
		$sql2 = "UPDATE managers SET draft_order = " . $old_place . " WHERE draft_id = " . $this->draft_id . " AND manager_id = " . $swap_manager_id;
		$manager_success = mysql_query($sql1);
		$swap_success = mysql_query($sql2);

		if(!$manager_success || !$swap_success)
			return false;

		return true;
	}

	/**
	 * Move the given manager down in their draft order
	 * @param int $manager_id Id of the manager to move up in the draft order
	 * @return bool Success on whether the move was completed successfully 
	 */
	public function moveManagerDown() {
		if($this->draft_order == 0)
			return false;

		$old_place = $this->draft_order;

		$lowest_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = " . $this->draft_id . " ORDER BY draft_order DESC LIMIT 1");

		if(!$lowest_order_row = mysql_fetch_array($lowest_order_result))
			return false;

		$lowest_order = intval($lowest_order_row['draft_order']);

		if($old_place == $lowest_order)
			return true;

		$new_place = $old_place + 1;

		$swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = " . $this->draft_id . " AND manager_id != " . $this->manager_id . " AND draft_order = " . $new_place);

		if(!$swap_manager_result)
			return false;

		$swap_manager_row = mysql_fetch_array($swap_manager_result);
		$swap_manager_id = intval($swap_manager_row['manager_id']);

		$sql1 = "UPDATE managers SET draft_order = " . $new_place . " WHERE draft_id = " . $this->draft_id . " AND manager_id = " . $this->manager_id;
		$sql2 = "UPDATE managers SET draft_order = " . $old_place . " WHERE draft_id = " . $this->draft_id . " AND manager_id = " . $swap_manager_id;
		$manager_success = mysql_query($sql1);
		$swap_success = mysql_query($sql2);

		if(!$manager_success || !$swap_success)
			return false;

		return true;
	}

	/**
	 * Saves or updates the manager object
	 * @return bool Success of the operation, be it an insert or update.
	 */
	public function saveManager() {
		if($this->manager_id > 0) {
			$sql = "UPDATE managers SET " .
				"manager_name = '" . mysql_real_escape_string($this->manager_name) . "', " .
				"manager_email = '" . mysql_real_escape_string($this->manager_email) . "' " .
				"WHERE manager_id = " . intval($this->manager_id) . " " .
				"AND draft_id = " . intval($this->draft_id);

			return mysql_query($sql);
		} elseif($this->draft_id > 0) {
			$sql = "INSERT INTO managers " .
				"(manager_id, draft_id, manager_name, manager_email, draft_order) " .
				"VALUES " .
				"(NULL, " . $this->draft_id . ", '" . mysql_real_escape_string($this->manager_name) . "', '" . mysql_real_escape_string($this->manager_email) . "', " . (intval($this->getLowestDraftorder() + 1)) . ")";

			$result = mysql_query($sql);
			if(!$result)
				return false;

			$this->manager_id = mysql_insert_id();

			return true;
		}else
			return false;
	}

	/**
	 * In order to get the lowest current draft number for this manager's draft.
	 * @return int Lowest draft order for the given draft 
	 */
	public function getLowestDraftorder() {
		$sql = "SELECT draft_order FROM managers WHERE draft_id = " . $this->draft_id . " ORDER BY draft_order DESC LIMIT 1";
		$row = mysql_fetch_array(mysql_query($sql));
		return intval($row['draft_order']);
	}

	public function deleteManager() {
		require_once('/models/draft_object.php');
		$draft = new draft_object($this->draft_id);

		if($draft->draft_id == 0 || !$draft->isUndrafted()) {
			return false;
		}

		$old_order = $this->draft_order;

		$sql = "DELETE FROM managers WHERE manager_id = " . $this->manager_id . " AND draft_id = " . $this->draft_id . " LIMIT 1";

		$success = mysql_query($sql);
		if(!$success)
			return false;

		$success = $this->cascadeNewDraftOrder($old_order);

		return $success;
	}
	
	public static function deleteManagersByDraft($draft_id) {
		$draft_id = intval($draft_id);

		if($draft_id == 0)
			return false;

		$managers = manager_object::getManagersByDraft($draft_id);

		$id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.	

		foreach($managers as $manager) {
			$id_string .= "," . $manager->manager_id;
		}

		$sql = "DELETE FROM managers WHERE manager_id IN (" . mysql_escape_string($id_string) . ")";

		return mysql_query($sql);
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
			$new_manager->manager_id = intval($manager_row['manager_id']);
			$new_manager->draft_id = intval($manager_row['draft_id']);
			$new_manager->manager_name = $manager_row['manager_name'];
			$new_manager->manager_email = $manager_row['manager_email'];
			$new_manager->draft_order = intval($manager_row['draft_order']);
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
			$inner_sql = "UPDATE managers SET draft_order = " . $old_order . " WHERE manager_id = " . intval($manager_row['manager_id']);
			if(!mysql_query($inner_sql))
				$success = false;
			$old_order++;
		}

		return $success;
	}
}

?>
