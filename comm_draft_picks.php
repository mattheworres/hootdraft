<?php
require('check_login.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("libraries/lib_draft.php");

set_conn();



$draft_id = intval($_REQUEST['draft_id']);
$pick_id = intval($_REQUEST['pick_id']);
$action = CleanString(trim($_REQUEST['action']));
$round = intval($_REQUEST['round']);
$pick = intval($_REQUEST['pick']);
$old_pick = intval($_REQUEST['old_pick']);
$manager_id = intval($_REQUEST['manager_id']);
$first_name = CleanString(trim($_REQUEST['first_name']));
$last_name = CleanString(trim($_REQUEST['last_name']));
$team_abbreviation = CleanString(trim($_REQUEST['team_abbreviation']));
$position = CleanString(trim($_REQUEST['position']));
$success = intval($_REQUEST['success']);

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."'");
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);

$pick_sql = "SELECT p.*, m.manager_name ".
	"FROM players p ".
	"LEFT OUTER JOIN managers m ".
	"ON m.manager_id = p.manager_id ".
	"WHERE p.draft_id = '".$draft_id."' ".
	"AND p.player_id = '".$pick_id."' ";

$pick_result = mysql_query($pick_sql);
$pick_row = mysql_fetch_array($pick_result);

if(empty($draft_id) || $draft_count == 0) {
    $title = "Draft Not Found";
    $msg = "The draft was not found.  Please go back and try again.";
    require('templates/error_page.php');
    exit(1);
}elseif($draft_row['draft_status'] == "undrafted") {
    $title = "Draft Not Ready!";
    $msg = "Your draft is currently not set to draft. Make sure all teams and settings are correct, and then change the draft status to \"In Progress\", and then come back and try again.";
    require('templates/error_page.php');
    exit(1);
}elseif($draft_row['draft_status'] == "complete") {
    $title = "Draft Already Complete!";
    $msg = "You can no longer draft because your draft is complete.";
    require('templates/error_page.php');
    exit(1);
}else {
    switch($action) {
	case 'add':
	    $title = "Enter the Next Draft Pick";

	    $current_pick = get_current_pick($draft_id);
	    $managers_result = get_managers($draft_id);
	    $next_picks = get_next_picks($draft_id, $current_pick['player_pick']);
	    $last_picks = get_last_picks($draft_id);

	    $on_deck = mysql_fetch_array($next_picks);
	    $in_the_hole = mysql_fetch_array($next_picks);
	    $on_the_bench = mysql_fetch_array($next_picks);
	    $grabbing_gatorade = mysql_fetch_array($next_picks);

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

	    if($success == 1)
		$msg = "Draft pick #".$old_pick." successfully added!";

	    require('templates/pick_add.php');
	    exit(0);
	    break;

	case 'add_pick':
	    if(empty($draft_id) ||
		    empty($round) ||
		    empty($pick) ||
		    empty($manager_id) ||
		    empty($first_name) ||
		    empty($last_name) ||
		    empty($team_abbreviation) ||
		    empty($position)) {
		$title = "Enter the Next Draft Pick";
		$err_msg = "You can not have any empty fields! Make sure ALL fields are filled out and re-submit.";

		$current_pick = get_current_pick($draft_id);
		$managers_result = get_managers($draft_id);

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

		require('templates/pick_add.php');
		exit(1);
	    }else {
		//mktime($hour,$min,$sec,$mon,$day,$year);
		$now = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
		$previous_pick = $pick - 1;

		if($previous_pick > 0) {
		    $start_time = get_previous_time($draft_id, $pick);
		    $start = strtotime($start_time['pick_time']);
		}else {
		    $start = strtotime($draft_row['draft_start_time']);
		}

		$alloted_time = $now - $start;

		$current_time = date("Y-m-d H:i:s");

		$sql = "UPDATE players SET ".
			"manager_id = '".$manager_id."', ".
			"first_name = '".$first_name."', ".
			"last_name = '".$last_name."', ".
			"team = '".$team_abbreviation."', ".
			"position = '".$position."', ".
			"pick_time = '".$current_time."', ".
			"pick_duration = '".$alloted_time."' ".
			"WHERE draft_id = '".$draft_id."' ".
			"AND player_round = '".$round."' ".
			"AND player_pick = '".$pick."' ";

		mysql_query($sql) or die(mysql_error());

		$next_pick = get_next_pick($draft_id, $pick);

		if($next_pick) {
		    $sql = "UPDATE draft SET ".
			    "draft_current_pick = '".$next_pick['player_pick']."', ".
			    "draft_current_round = '".$next_pick['player_round']."' ".
			    "WHERE draft_id = '".$draft_id."'";

		    mysql_query($sql) or die(mysql_error());

		    header('Location: comm_draft_picks.php?action=add&draft_id='.$draft_id.'&success=1&old_pick='.$pick);
		    exit(0);
		}else {
		    $sql = "UPDATE draft SET ".
			    "draft_status = 'complete', ".
			    "draft_end_time = '".$current_time."' ".
			    "WHERE draft_id = '".$draft_id."'";

		    mysql_query($sql);
		    header('Location: comm_manage_draft.php?did='.$draft_id);
		    exit(0);
		}

	    }
	    break;

	case 'select_edit':
	    $title = "Edit a Draft Pick";
	    $rounds = $draft_row['draft_rounds'];
	    if($success)
		$msg = "Draft pick round ".$round.", #".$pick." successfully edited!";

	    require('templates/pick_edit_select.php');
	    exit(0);
	    break;

	case 'edit':
	    $title = "Edit Draft Pick - ".$pick_row['manager_name']."'s #".$pick_row['player_pick']." Pick";
	    $managers_result = get_managers($draft_id);

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
	    
	    require('templates/pick_edit.php');
	    exit(0);
	    break;

	case 'edit_pick':
	    if(empty($draft_id) ||
		    empty($pick_id) ||
		    empty($manager_id) ||
		    empty($first_name) ||
		    empty($last_name) ||
		    empty($team_abbreviation) ||
		    empty($position)) {
		$title = "Edit Draft Pick - ".$pick_row['manager_name']."'s #".$pick_row['player_pick']." Pick";
		$err_msg = "You can not have any empty fields! Make sure ALL fields are filled out and re-submit.";
		$managers_result = get_managers($draft_id);

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

		require('templates/pick_edit.php');
		exit(1);
	    }else {
		$sql = "UPDATE players SET ".
			"manager_id = '".$manager_id."', ".
			"first_name = '".$first_name."', ".
			"last_name = '".$last_name."', ".
			"team = '".$team_abbreviation."', ".
			"position = '".$position."' ".
			"WHERE draft_id = '".$draft_id."' ".
			"AND player_id = '".$pick_id."' ";
		mysql_query($sql) or die(mysql_error());

		header('Location: comm_draft_picks.php?action=select_edit&draft_id='.$draft_id.'&success=1&round='.$pick_row['player_round'].'&pick='.$pick_row['player_pick']);
		exit(0);
	    }
	    break;

	case 'get_round_picks':
	    $picks = get_round_picks($draft_id, $round);

	    $html = "<p><label for=\"round\">Round*:</label>
			    <select name=\"round\" id=\"round\">";
	    for($i = 1; $i < $draft_row['draft_rounds']; $i++) {
		$html .= "<option value=\"".$i."\"".($i == $round ? " selected" : "").">Round ".$i."</option>\n";
	    }

	    $html .= "</select></p>\n".
		    "<p><label for=\"pick_id\">Editable Picks*:</label>".
		    "<select name=\"pick_id\">";

	    $count = mysql_num_rows($picks);
	    if($count == 0)
		$html .= "<option value=\"\" disabled>No Picks Made in Round ".$round."</option>";
	    else {
		while($pick = mysql_fetch_array($picks)) {
		    $html .= "<option value=\"".$pick['player_id']."\">Pick ".$pick['player_pick']." - ".$pick['manager_name']."</option>";
		}
	    }

	    $html .= "</select></p>";
	    echo $html;
	    exit(0);
	    break;
    }
}
?>
