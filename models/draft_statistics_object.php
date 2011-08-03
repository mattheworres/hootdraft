<?php
require_once("/models/player_object.php");
require_once("/models/manager_object.php");
require_once("/models/draft_object.php");
require_once("/libraries/php_draft_library.php");

class draft_statistics_object {
	public $hold_on_manager_name;
	public $hold_on_pick_time;
	public $quickie_manager;
	public $quickie_pick_time;
	public $speedy_manager_name;
	public $speedy_pick_time;
	public $average_pick_time;
	public $longest_round_time;
	public $shortest_round_time;
	public $most_drafted_team;
	public $most_drafted_team_count;
	public $least_drafted_team;
	public $least_drafted_team_count;
	public $most_drafted_position;
	public $most_drafted_position_count;
	public $least_drafted_position;
	public $least_drafted_position_count;
	private $draft_id;
	
	public function generateStatistics($draft_id) {
		$this->draft_id = intval($draft_id);
	}
	
	private function generateHoldOnAward() {
		
	}
	
	private function generateAveragePickTime() {
		$sql = "SELECT avg(pick_duration) as pick_average
		FROM players p
		WHERE p.draft_id = ".$this->draft_id."
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		$this->average_pick_time = php_draft_library::secondsToWords(intval($row['pick_average']));
	}
}
?>
