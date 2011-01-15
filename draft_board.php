<?php
require('check_draft_password.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("libraries/lib_draft.php");

set_conn();


$draft_id = intval($_REQUEST['draft_id']);
$action = CleanString(trim($_REQUEST['action']));
$pick = intval($_REQUEST['pick']);

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
	case 'check_pick':
	    if($draft_row['draft_status'] == "complete")
		echo "9999";
	    else
		echo intval($draft_row['draft_current_pick']);
	    exit(0);
	    break;
	
	case 'load_board':
	    $rounds = $draft_row['draft_rounds'];
	    $managers = get_managers($draft_id);
	    $number_of_managers = mysql_num_rows($managers);
	    $col_width = 115;
	    $total_width = 10 + ($col_width * intval($number_of_managers));
	    $picks_result = Array();

	    if($draft_row['draft_style'] == "standard") {
		for($i = 1; $i <= $rounds; $i++) {
		    $picks_result[$i] = get_all_round_picks($draft_id, $i);
		}
	    }elseif($draft_row['draft_style'] == "serpentine") {
		$sort = "ASC";
		for($i = 1; $i <= $rounds; $i++) {
		    $picks_result[$i] = get_all_round_picks($draft_id, $i, $sort);
		    if($sort == "ASC")
			$sort = "DESC";
		    else
			$sort = "ASC";
		}
	    }
	    $html = "";

	    if($draft_row['draft_status'] == "complete")
		$html .= "<p class=\"success\">The draft has been completed!</p>";
	    
	    $html .= "<table id=\"draft_table\" width=\"".$total_width."\"><tr><th class=\"left_col\">Rd.</th><th class=\"left_col\" colspan=\"".$number_of_managers."\">".$draft_row['draft_name']." - Draft Board</th></tr>";
	    for($i = 1; $i <= $rounds; $i++) {
		$html .= "<tr><td class=\"left_col\" width=\"10\">".$i."</td>";
		while($pick_row = mysql_fetch_array($picks_result[$i])) {
		    if($pick_row['pick_time'] != '') {
			$html .= "<td width=\"".$col_width."\" bgcolor=\"".$position_colors[$pick_row['position']]."\">#  ".$pick_row['player_pick']."<br /><strong>".$pick_row['first_name']."<br />".$pick_row['last_name']."</strong><br />(".$pick_row['position']." - ".$pick_row['team'].")<br />".$pick_row['manager_name']."</td>";
		    }else {
			$html .= "<td width=\"".$col_width."\"># ".$pick_row['player_pick']."<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />".$pick_row['manager_name']."</td>";
		    }
		}
	    }
	    $html .= "</table>";
	    if($draft_row['draft_status'] == "complete")
		$html .= "<p class=\"success\">The draft has been completed!</p>";
	    echo $html;
	    exit(0);
	    break;

	default:
	    $rounds = $draft_row['draft_rounds'];
	    $managers = get_managers($draft_id);
	    $number_of_managers = mysql_num_rows($managers);
	    $picks_total = intval($rounds) * intval($number_of_managers);
	    $col_width = 115;
	    $total_width = 10 + ($col_width * intval($number_of_managers));
	    $picks_result = Array();

	    if($draft_row['draft_style'] == "standard") {
		for($i = 1; $i <= $rounds; $i++) {
		    $picks_result[$i] = get_all_round_picks($draft_id, $i);
		}
	    }elseif($draft_row['draft_style'] == "serpentine") {
		$sort = "ASC";
		for($i = 1; $i <= $rounds; $i++) {
		    $picks_result[$i] = get_all_round_picks($draft_id, $i, $sort);
		    if($sort == "ASC")
			$sort = "DESC";
		    else
			$sort = "ASC";
		}
	    }
	    require('views/draft_board.php');
	    exit(0);
	    break;
    }
}
?>
