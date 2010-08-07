<?php
require('check_login.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("libraries/lib_draft.php");

set_conn();


$draft_id = intval($_REQUEST['draft_id']);
$action = CleanString(trim($_REQUEST['action']));

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."'");
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);

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
    $title = "Draft Room Closed";
    $success_msg = "The draft room is officially closed.  Your draft has been completed, all picks have been made.  It's now time to mosey on over to the public draft board to see all of the picks, check out some stats, and then export the draft so you can enter it later at your leisure.";
    require('templates/error_page.php');
    exit(0);
}else {
    $title = "Draft Room Main Menu - ".$draft_row['draft_name'];
    $picks_result = get_last_ten_picks($draft_id);
    require('templates/draft_room.php');
    exit(0);
}
?>
