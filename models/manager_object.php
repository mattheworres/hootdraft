<?php
/**
 * Represents a PHPDraft "manager" object.
 *
 * Managers have many players (picks), and belong to a single draft.
 *
 * @property int $manager_id The unique identifier for this manager
 * @property int $draft_id Foreign key to the draft this manager belongs to
 * @property string $manager_name Textual display name for each manager
 * @property string $team_name Deprecated
 * @property int $draft_order The order in which the manager makes a pick in the draft.
 */
class manager_object {
	public $manager_id;
	public $draft_id;
	public $manager_name;
	public $team_name;
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
		$this->team_name = $manager_row['team_name'];
		$this->draft_order = intval($manager_row['draft_order']);
		
		return true;
	}

	/**
	 * Given a single draft ID, get all managers for that draft
	 * @param int $draft_id
	 * @param bool $draft_order_sort Whether or not to sort by the manager's order in the draft. If false, manager_name is used
	 * @return array of manager objects
	 */
	public static function getManagersByDraftId($draft_id, $draft_order_sort = false) {
		$managers = array();
		$sql = "SELECT * FROM managers WHERE draft_id = '" . $draft_id . "' ORDER BY ";
		$sql .= $draft_order_sort ? "draft_order" : "manager_name";
		
		$managers_result = mysql_query($sql);

		while($manager_row = mysql_fetch_array($managers_result)) {
			$new_manager = new manager_object();
			$new_manager->manager_id = intval($manager_row['manager_id']);
			$new_manager->draft_id = intval($manager_row['draft_id']);
			$new_manager->manager_name = $manager_row['manager_name'];
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
	public static function getCountOfManagersByDraftId($draft_id) {
		$sql = "SELECT manager_id FROM managers WHERE draft_id = " . $draft_id . " ORDER BY manager_name";

		return mysql_num_rows(mysql_query($sql));
	}
	
	/**
	 * Move the given manager up in their draft order
	 * @param int $manager_id Id of the manager to move up in the draft order
	 * @return bool success on whether the move was completed successfully 
	 */
	public static function moveManagerUp($manager_id) {
		$manager = new manager_object($manager_id);
		
		if(!$manager || $manager->draft_order == 0)
			return false;

		$old_place = $manager->draft_order;

		if($old_place == 1)
			return true;

		$new_place = $old_place - 1;

		$swap_manager_result = mysql_query("SELECT manager_id FROM managers WHERE draft_id = " . $manager->draft_id . " AND manager_id != " . $manager->manager_id . " AND draft_order = " . $new_place);
		
		if(!$swap_manager_result)
			return false;
		
		$swap_manager_row = mysql_fetch_array($swap_manager_result);
		
		$swap_manager_id = intval($swap_manager_row['manager_id']);

		$sql1 = "UPDATE managers SET draft_order = " . $new_place . " WHERE draft_id = " . $manager->draft_id . " AND manager_id = " . $manager->manager_id;
		$sql2 = "UPDATE managers SET draft_order = " . $old_place . " WHERE draft_id = " . $manager->draft_id . " AND manager_id = " . $swap_manager_id;
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
	public static function moveManagerDown($manager_id) {
		$manager = new manager_object($manager_id);
		
		if(!$manager || $manager->draft_order == 0)
			return false;

		$old_place = $manager->draft_order;

		$lowest_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = " . $manager->draft_id . " ORDER BY draft_order DESC LIMIT 1");
		
		if(!$lowest_order_row = mysql_fetch_array($lowest_order_result))
			return false;
		
		$lowest_order = intval($lowest_order_row['draft_order']);

		if($old_place == $lowest_order)
			return true;

		$new_place = $old_place + 1;

		$swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = " . $manager->draft_id . " AND manager_id != " . $manager->manager_id . " AND draft_order = " . $new_place);
		
		if(!$swap_manager_result)
			return false;
		
		$swap_manager_row = mysql_fetch_array($swap_manager_result);
		$swap_manager_id = intval($swap_manager_row['manager_id']);

		$sql1 = "UPDATE managers SET draft_order = " . $new_place . " WHERE draft_id = " . $manager->draft_id . " AND manager_id = " . $manager->manager_id;
		$sql2 = "UPDATE managers SET draft_order = " . $old_place . " WHERE draft_id = " . $manager->draft_id . " AND manager_id = " . $swap_manager_id;
		$manager_success = mysql_query($sql1);
		$swap_success = mysql_query($sql2);

		if(!$manager_success || !$swap_success)
			return false;
			
		return true;
	}
}

?>
