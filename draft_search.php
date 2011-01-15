<?php
require('check_draft_password.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("models/draft_model.php");

date_default_timezone_set('America/New_York');

set_conn();


$draft_id = intval($_REQUEST['draft_id']);
$action = CleanString(trim($_REQUEST['action']));
$search = CleanString(trim($_REQUEST['search']));
$search = str_replace(",","",$search);

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
    switch($action) {
	case 'load_search':
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

	    $search_results = search_draft($draft_id, $search);
	    $number_of_results = mysql_num_rows($search_results);

	    if($number_of_results == 0) {
		$search_results = search_draft_basic($draft_id, $search);
		$number_of_results = mysql_num_rows($search_results);
	    }
	    
	    if($number_of_results > 0) {

	    $html = "<fieldset>".
			"<legend>Search Results for <em>\"".$search."\"</em></legend>";
		$html .= "<p class=\"success\"><strong>Searched at </strong> ". date("h:i:s A")."</p>";
		$html .= "<table width=\"100%\">".
			"<tr>".
			"<th width=\"40\">Pick</th>".
			"<th>Player</th>".
			"<th width=\"120\">Position</th>".
			"<th width=\"160\">Team</th>".
			"<th width=\"120\">Manager</th>".
			"</tr>";
		while($pick_row = mysql_fetch_array($search_results)) {
		    $html .= "<tr style=\"background-color: ".$position_colors[$pick_row['position']].";\"><td>".$pick_row['player_pick']."</td><td><strong>".$pick_row['first_name']." ".$pick_row['last_name']."</strong></td><td>".$positions[$pick_row['position']]."</td><td>".$teams[$pick_row['team']]."</td><td>".$pick_row['manager_name']."</td></tr>";
		}
		$html .= "</table></fieldset>";
	    }else {
		$html = "<fieldset><legend>Search Results for <em>\"".$search."\"</em></legend><p class=\"error\"><strong>0 results found for \"".$search."\" at ". date("h:i:s A")."</strong></p></table></fieldset>";
	    }

	    echo $html;
	    exit(0);
	    break;

	default:
	    $title = $draft_row['draft_name'] . " - Search";
	    $msg = "Enter a player name (first, last or both) in the search box below, and then hit \"Search\".";
	    $number_of_rounds = $draft_row['draft_rounds'];
	    require('views/draft_search.php');
	    exit(0);
	    break;
    }
}
?>
