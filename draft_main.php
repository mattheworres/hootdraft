<?php
require('check_draft_password.php');
include_once("dbconn.php");
include_once("cleanstring.php");
include_once("models/draft_model.php");

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
    $title = "Draft Home Page - ".$draft_row['draft_name'];
    $picks_result = get_last_ten_picks($draft_id);
    $start_time = strtotime($draft_row['draft_start_time']);
    $end_time = strtotime($draft_row['draft_end_time']);
    $draft_row['draft_start_time'] = date("l, F jS \a\\t g:iA", $start_time);
    $draft_row['draft_end_time'] = date("l, F jS \a\\t g:iA", $end_time);
    $elapsed_time = $end_time - $start_time;
    $elapsed_time = seconds_to_words($elapsed_time);

    require('views/draft_main.php');
    exit(0);
}
?>
