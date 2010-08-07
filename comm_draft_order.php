<?php
require('check_login.php');
include_once("dbconn.php");
include_once("cleanstring.php");

set_conn();
select_db("scsports_phpdraft");

$draft_id = intval($_REQUEST['did']);
$manager_id = intval($_REQUEST['mid']);
$action = CleanString(trim($_REQUEST['action']));

$draft_result = mysql_query("SELECT draft_status FROM draft WHERE draft_id = '".$draft_id."'");
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);

if(empty($draft_id) || $draft_count < 1) {
    $title = "Draft Not Found";
    $msg = "The draft that you were attempting to update the draft order for was not found.  Please go back and try again.";
    require('templates/error_page.php');
    exit(1);
}elseif($draft_row['draft_status'] != "undrafted") {
    $title = "Draft Not Editable";
    $msg = "You cannot change the draft order unless the draft is in the \"undrafted\" or setup status. (Current status is \"".$draft_row['draft_status']."\")";
    require('templates/error_page.php');
    exit(1);
}elseif(empty($manager_id)) {
    $title = "Manager Not Found";
    $msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
    require('templates/error_page.php');
    exit(1);
}elseif(empty($action)) {
    $title = "Invalid Request";
    $msg = "Your request to update the draft's order was not correct.  Please go back and try again.";
    require('templates/error_page.php');
    exit(1);
}else {
    switch($action) {
	case 'up':
	    $manager_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."' LIMIT 1");
	    if(!$manager_row = mysql_fetch_array($manager_result)) {
		$title = "Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('templates/error_page.php');
		exit(1);
	    }

	    $old_place = intval($manager_row['draft_order']);

	    if($old_place == 1) {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
	    }

	    $new_place = intval($manager_row['draft_order']) - 1;

	    $swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = '".$draft_id."' AND manager_id != '".$manager_id."' AND draft_order = '".$new_place."'");
	    if(!$swap_manager_row = mysql_fetch_array($swap_manager_result)) {
		$title = "Swap Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('templates/error_page.php');
		exit(1);
	    }

	    $sql1 = "UPDATE managers SET draft_order = '".$new_place."' WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."'";
	    $sql2 = "UPDATE managers SET draft_order = '".$old_place."' WHERE draft_id = '".$draft_id."' AND manager_id = '".$swap_manager_row['manager_id']."'";
	    $manager_success = mysql_query($sql1);
	    $swap_success = mysql_query($sql2);

	    if(!$manager_success || !$swap_success) {
		$title = "Draft Order Not Updated";
		$msg = "An error occurred and the draft order wasn't updated.  Please go back and try again.<br/><br/>".$sql1."<br/><br/>".$sql2;
		require('templates/error_page.php');
		exit(1);
	    }else {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
	    }
	    break;

	case 'down':
	    $manager_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."' LIMIT 1");
	    if(!$manager_row = mysql_fetch_array($manager_result)) {
		$title = "Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('templates/error_page.php');
		exit(1);
	    }

	    $old_place = intval($manager_row['draft_order']);

	    $lowest_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order DESC LIMIT 1");
	    if(!$lowest_order_row = mysql_fetch_array($lowest_order_result)) {
		$title = "Draft Not Found";
		$msg = "The draft that you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('templates/error_page.php');
		exit(1);
	    }
	    $lowest_order = intval($lowest_order_row['draft_order']);

	    if($old_place == $lowest_order) {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
	    }

	    $new_place = intval($manager_row['draft_order']) + 1;

	    $swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = '".$draft_id."' AND manager_id != '".$manager_id."' AND draft_order = '".$new_place."'");
	    if(!$swap_manager_row = mysql_fetch_array($swap_manager_result)) {
		$title = "Swap Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('templates/error_page.php');
		exit(1);
	    }
	    $sql1 = "UPDATE managers SET draft_order = '".$new_place."' WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."'";
	    $sql2 = "UPDATE managers SET draft_order = '".$old_place."' WHERE draft_id = '".$draft_id."' AND manager_id = '".$swap_manager_row['manager_id']."'";
	    $manager_success = mysql_query($sql1);
	    $swap_success = mysql_query($sql2);

	    if(!$manager_success || !$swap_success) {
		$title = "Draft Order Not Updated";
		$msg = "An error occurred and the draft order wasn't updated.  Please go back and try again.<br/><br/>".$sql1."<br/><br/>".$sql2;
		require('templates/error_page.php');
		exit(1);
	    }else {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
	    }
	    break;
    }
}
?>
