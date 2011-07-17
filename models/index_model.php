<?php

require_once("models/draft_object.php");

class indexObject {
	public $number_of_drafts;
	public $draft_objects = array();

	public function set_drafts() {
		$draft_result = mysql_query("SELECT * FROM draft ORDER by draft_status DESC");

		while($draft_row = mysql_fetch_array($draft_result)) {
			$draft_object = new draft_object();
			$draft_object->draft_id = $draft_row['draft_id'];
			$draft_object->draft_name = $draft_row['draft_name'];
			$draft_object->draft_sport = $draft_row['draft_sport'];

			$draft_object->visibility = ($draft_row['draft_password'] != '' ? "locked" : "unlocked");

			$draft_object->setStatus($draft_row['draft_status']);

			$this->draft_objects[] = $draft_object;
		}
		
		$this->number_of_drafts = count($this->draft_objects);
	}
}

?>
