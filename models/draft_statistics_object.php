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
	public $slowpoke_manager_name;
	public $slowpoke_pick_time;
	public $speedy_manager_name;
	public $speedy_pick_time;
	public $average_pick_time;
	public $longest_round;
	public $longest_round_time;
	public $shortest_round;
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
	private $sports_teams;
	private $sports_positions;
	
	public function generateStatistics(draft_object $draft) {
		$this->draft_id = intval($draft->draft_id);
		$this->sports_teams = $draft->sports_teams;
		$this->sports_positions = $draft->sports_positions;
		
		$this->generateHoldOnAward();
		$this->generateQuickieAward();
		$this->generateSlowpokeAward();
		$this->generateSpeedyAward();
		$this->generateAveragePickTime();
		$this->generateRoundTimes();
		$this->generateTeamSuperlatives();
		$this->generatePositionSuperlatives();
	}
	
	// <editor-fold defaultstate="collapsed" desc="Private Stat Generators">
	private function generateHoldOnAward() {
		$sql = "SELECT p.pick_duration, m.manager_name, avg(pick_duration) as pick_average
		FROM players p
		LEFT OUTER JOIN managers m
		ON m.manager_id = p.manager_id
		WHERE p.draft_id = " . $this->draft_id . "
		GROUP BY m.manager_name 
		ORDER BY pick_average DESC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->hold_on_manager_name = $row['manager_name'];
		$this->hold_on_pick_time = php_draft_library::secondsToWords(intval($row['pick_average']));
	}
	
	private function generateQuickieAward() {
		$sql = "SELECT p.pick_duration, m.manager_name, avg(pick_duration) as pick_average
		FROM players p
		LEFT OUTER JOIN managers m
		ON m.manager_id = p.manager_id
		WHERE p.draft_id = " . $this->draft_id . "
		GROUP BY m.manager_name
		ORDER BY pick_average ASC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->quickie_manager = $row['manager_name'];
		$this->quickie_pick_time = php_draft_library::secondsToWords(intval($row['pick_average']));
	}
	
	private function generateSlowpokeAward() {
		$sql = "SELECT p.pick_duration, p.player_pick, m.manager_name, max(pick_duration) as pick_max
		FROM players p
		LEFT OUTER JOIN managers m
		ON m.manager_id = p.manager_id
		WHERE p.draft_id = " . $this->draft_id . "
		GROUP BY m.manager_name
		ORDER BY pick_max DESC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->slowpoke_manager_name = $row['manager_name'];
		$this->slowpoke_pick_time = php_draft_library::secondsToWords(intval($row['pick_max']));
	}
	
	private function generateSpeedyAward() {
		$sql = "SELECT p.pick_duration, p.player_pick, m.manager_name, min(pick_duration) as pick_min
		FROM players p
		LEFT OUTER JOIN managers m
		ON m.manager_id = p.manager_id
		WHERE p.draft_id = " . $this->draft_id . "
		GROUP BY m.manager_name
		ORDER BY pick_min ASC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->speedy_manager_name = $row['manager_name'];
		$this->speedy_pick_time = php_draft_library::secondsToWords(intval($row['pick_min']));
	}
	
	private function generateAveragePickTime() {
		$sql = "SELECT avg(pick_duration) as pick_average
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		$this->average_pick_time = php_draft_library::secondsToWords(intval($row['pick_average']));
	}
	
	private function generateRoundTimes() {
		$sql = "SELECT DISTINCT p.player_round, sum( p.pick_duration ) AS round_time
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.pick_duration IS NOT NULL
		GROUP BY player_round
		ORDER BY round_time DESC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->longest_round = intval($row['player_round']);
		$this->longest_round_time = php_draft_library::secondsToWords(intval($row['round_time']));
		
		$sql = "SELECT DISTINCT p.player_round, sum( p.pick_duration ) AS round_time
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.pick_duration IS NOT NULL
		GROUP BY player_round
		ORDER BY round_time ASC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->shortest_round = intval($row['player_round']);
		$this->shortest_round_time = php_draft_library::secondsToWords(intval($row['round_time']));
	}
	
	private function generateTeamSuperlatives() {
		$sql = "SELECT DISTINCT p.team, count(team) as team_occurences
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.team IS NOT NULL
		GROUP BY team
		ORDER BY team_occurences DESC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->most_drafted_team = $this->sports_teams[$row['team']];
		$this->most_drafted_team_count = intval($row['team_occurences']);
		
		$sql = "SELECT DISTINCT p.team, count(team) as team_occurences
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.team IS NOT NULL
		GROUP BY team
		ORDER BY team_occurences ASC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->least_drafted_team = $this->sports_teams[$row['team']];
		$this->least_drafted_team_count = intval($row['team_occurences']);
	}
	
	private function generatePositionSuperlatives() {
		$sql = "SELECT DISTINCT p.position, count(position) as position_occurences
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.position IS NOT NULL
		GROUP BY position
		ORDER BY position_occurences DESC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->most_drafted_position = $this->sports_positions[$row['position']];
		$this->most_drafted_position_count = intval($row['position_occurences']);
		
		$sql = "SELECT DISTINCT p.position, count(position) as position_occurences
		FROM players p
		WHERE p.draft_id = " . $this->draft_id . "
		AND p.position IS NOT NULL
		GROUP BY position
		ORDER BY position_occurences ASC
		LIMIT 1";
		
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$this->least_drafted_position = $this->sports_positions[$row['position']];
		$this->least_drafted_position_count = intval($row['position_occurences']);
	}
	// </editor-fold>
}
?>
