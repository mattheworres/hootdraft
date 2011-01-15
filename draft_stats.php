<?php
require('check_draft_password.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("models/draft_model.php");

date_default_timezone_set('America/New_York');

set_conn();


$draft_id = intval($_REQUEST['draft_id']);
$action = CleanString(trim($_REQUEST['action']));

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."'");
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);


if(empty($draft_id) || $draft_count == 0) {
    $title = "Draft Not Found";
    $msg = "The draft was not found.  Please go back and try again.";
    require('views/error_page.php');
    exit(1);
}elseif($draft_row['draft_status'] == "undrafted") {
    $title = "Draft Not Ready!";
    $msg = "This draft is currently not drafting.  Your commissioner must change the draft to \"in progress\" before you can view the draft board.";
    require('views/error_page.php');
    exit(1);
}else {
    $stats = get_summary_stats($draft_id);

    if($draft_row['draft_sport'] == "hockey") {
	$teams = $nhl_teams;
	$positions = $nhl_positions;
    }elseif($draft_row['draft_sport'] == "football") {
	$teams = $nfl_teams;
	$positions = $nfl_positions;
    }elseif($draft_row['draft_sport'] == "baseball") {
	$teams = $mlb_teams;
	$positions = $mlb_positions;
    }elseif($draft_row['draft_sport'] == "basketball") {
	$teams = $nba_teams;
	$positions = $nba_positions;
    }

    switch($action) {
	case 'reload_stats':
	    $html = "<p class=\"success\">Last refreshed: ". date("h:i:s A")."</p>".
	    "<p><strong>Longest Average Pick Time (The &quot;Hooooold Oonnn&quot; Award)</strong><br />".$stats['Average_Time_High']['manager_name'] . " - " . seconds_to_words($stats['Average_Time_High']['pick_average'])."</p>".
	    "<p><strong>Shortest Average Pick Time (The Quickie Award)</strong><br />".$stats['Average_Time_Low']['manager_name'] . " - " . seconds_to_words($stats['Average_Time_Low']['pick_average'])."</p>".
	    "<p><strong>Longest Single Pick (The Slowpoke Rodriguez Award)</strong><br />".$stats['High_Time']['manager_name'] . " - " . seconds_to_words($stats['High_Time']['pick_max'])."</p>".
	    "<p><strong>Shortest Single Pick (The Speedy Gonzalez Award)</strong><br />".$stats['Low_Time']['manager_name'] . " - " . seconds_to_words($stats['Low_Time']['pick_min'])."</p>".
	    "<p><strong>Average Pick Time</strong><br />".seconds_to_words($stats['Average_Time']['pick_average'])."</p>".
	    "<p><strong>Longest Round Time</strong><br />Round #".$stats['Round_Time_High']['player_round'] . " - " . seconds_to_words($stats['Round_Time_High']['round_time'])."</p>".
	    "<p><strong>Shortest Round Time</strong><br />Round #".$stats['Round_Time_Low']['player_round'] . " - " . seconds_to_words($stats['Round_Time_Low']['round_time'])."</p>".
	    "<p><strong>Most Drafted Team</strong><br />".$teams[$stats['High_Team']['team']] . " - " . $stats['High_Team']['team_occurences']." of their players drafted</p>".
	    "<p><strong>Least Drafted Team</strong><br />".$teams[$stats['Low_Team']['team']] . " - " . $stats['Low_Team']['team_occurences']." of their players drafted</p>".
	    "<p><strong>Most Drafted Position</strong><br />".$positions[$stats['High_Position']['position']] . " - " . $stats['High_Position']['position_occurences']." of them drafted</p>".
	    "<p><strong>Least Drafted Position</strong><br />".$positions[$stats['Low_Position']['position']] . " - " . $stats['Low_Position']['position_occurences']." of them drafted</p>";
	    echo $html;
	    exit(0);
	    break;

	default:
	    $title = "Draft Statistics Summary - ". $draft_row['draft_name'];
	
	    require('views/draft_stats_main.php');
	    exit(0);
	    break;
    }
}
?>
