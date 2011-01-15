<?php
require('check_draft_password.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("libraries/lib_draft.php");

date_default_timezone_set('America/New_York');

set_conn();


$draft_id = intval($_REQUEST['draft_id']);
$round = intval($_REQUEST['round']);
$action = CleanString(trim($_REQUEST['action']));

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."'");
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);

$picks_result = get_round_picks($draft_id, $round, "ASC");
$picks_count = mysql_num_rows($picks_result);

if($picks_count > 0)
    $have_picks = true;
else 
    $have_picks = false;


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
    switch($action) {
	case 'load_round':
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

	    if(!$have_picks) {
		$html = "<p>Round ".$round." has no picks so far. Please select another round.</p>";
	    }else {
		$html = "<fieldset>".
			"<legend>Round #".$round."</legend>";
		if($draft_row['draft_status'] != "complete")
		    $html .= "<p class=\"success\"><strong>Last refreshed:</strong> ". date("h:i:s A")."</p>";
		$html .= "<table width=\"100%\">".
			"<tr>".
			"<th width=\"40\">Pick</th>".
			"<th>Player</th>".
			"<th width=\"120\">Position</th>".
			"<th width=\"160\">Team</th>".
			"<th width=\"120\">Manager</th>".
			"</tr>";
		while($pick_row = mysql_fetch_array($picks_result)) {
		    $html .= "<tr style=\"background-color: ".$position_colors[$pick_row['position']].";\"><td>".$pick_row['player_pick']."</td><td><strong>".$pick_row['first_name']." ".$pick_row['last_name']."</strong></td><td>".$positions[$pick_row['position']]."</td><td>".$teams[$pick_row['team']]."</td><td>".$pick_row['manager_name']."</td></tr>";
		}
		$html .= "</table></fieldset>";
	    }

	    echo $html;
	    exit(0);
	    break;

	default:
	    $title = "Round Draft Picks - ".$draft_row['draft_name'];
	    $number_of_rounds = $draft_row['draft_rounds'];
	    require('views/draft_rounds.php');
	    exit(0);
	    break;
    }
}
?>
