<?php
require('check_login.php');
include_once("dbconn.php");
include_once("cleanstring.php");

set_conn();

$draft_id = intval($_GET['did']);
$manager_id = intval($_GET['mid']);
$action = $_GET['action'];

$draft_result = mysql_query("SELECT draft_status FROM draft WHERE draft_id = " . $draft_id);
$draft_count = mysql_num_rows($draft_result);
$draft_row = mysql_fetch_array($draft_result);

// <editor-fold defaultstate="collapsed" desc="Error Checking">
if($draft_id == 0 || $draft_count < 1) {
	$title = "Draft Not Found";
	$msg = "The draft that you were attempting to update the draft order for was not found.  Please go back and try again.";
	require('views/error_page.php');
	exit(1);
}elseif($draft_row['draft_status'] != "undrafted") {
	$title = "Draft Not Editable";
	$msg = "You cannot change the draft order unless the draft is in the \"undrafted\" or setup status. (Current status is \"".$draft_row['draft_status']."\")";
	require('views/error_page.php');
	exit(1);
}elseif($manager_id == 0) {
	$title = "Manager Not Found";
	$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
	require('views/error_page.php');
	exit(1);
}elseif(!isset($action) || strlen($action) == 0) {
	$title = "Invalid Request";
	$msg = "Your request to update the draft's order was not correct.  Please go back and try again.";
	require('views/error_page.php');
	exit(1);
}
// </editor-fold>

switch($action) {
	case 'up':
		// <editor-fold defaultstate="collapsed" desc="Up Logic">
		$manager_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = ".$draft_id." AND manager_id = ".$manager_id." LIMIT 1");
		if(!$manager_row = mysql_fetch_array($manager_result)) {
		$title = "Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('views/error_page.php');
		exit(1);
		}

		$old_place = intval($manager_row['draft_order']);

		if($old_place == 1) {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
		}

		$new_place = intval($manager_row['draft_order']) - 1;

		$swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = ".$draft_id." AND manager_id != ".$manager_id." AND draft_order = '".$new_place."'");
		if(!$swap_manager_row = mysql_fetch_array($swap_manager_result)) {
		$title = "Swap Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('views/error_page.php');
		exit(1);
		}

		$sql1 = "UPDATE managers SET draft_order = '".$new_place."' WHERE draft_id = ".$draft_id." AND manager_id = ".$manager_id;
		$sql2 = "UPDATE managers SET draft_order = '".$old_place."' WHERE draft_id = ".$draft_id." AND manager_id = ".$swap_manager_row['manager_id']."'";
		$manager_success = mysql_query($sql1);
		$swap_success = mysql_query($sql2);

		if(!$manager_success || !$swap_success) {
		$title = "Draft Order Not Updated";
		$msg = "An error occurred and the draft order wasn't updated.  Please go back and try again.<br/><br/>".$sql1."<br/><br/>".$sql2;
		require('views/error_page.php');
		exit(1);
		}else {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
		}
		// </editor-fold>
		break;

	case 'down':
		// <editor-fold defaultstate="collapsed" desc="Down Logic">
		$manager_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = ".$draft_id." AND manager_id = ".$manager_id." LIMIT 1");
		if(!$manager_row = mysql_fetch_array($manager_result)) {
		$title = "Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('views/error_page.php');
		exit(1);
		}

		$old_place = intval($manager_row['draft_order']);

		$lowest_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = ".$draft_id." ORDER BY draft_order DESC LIMIT 1");
		if(!$lowest_order_row = mysql_fetch_array($lowest_order_result)) {
		$title = "Draft Not Found";
		$msg = "The draft that you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('views/error_page.php');
		exit(1);
		}
		$lowest_order = intval($lowest_order_row['draft_order']);

		if($old_place == $lowest_order) {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
		}

		$new_place = intval($manager_row['draft_order']) + 1;

		$swap_manager_result = mysql_query("SELECT draft_order, manager_id FROM managers WHERE draft_id = ".$draft_id." AND manager_id != ".$manager_id." AND draft_order = '".$new_place."'");
		if(!$swap_manager_row = mysql_fetch_array($swap_manager_result)) {
		$title = "Swap Manager Not Found in Database";
		$msg = "The manager you were attempting to update the draft order for was not found.  Please go back and try again.";
		require('views/error_page.php');
		exit(1);
		}
		$sql1 = "UPDATE managers SET draft_order = '".$new_place."' WHERE draft_id = ".$draft_id." AND manager_id = ".$manager_id;
		$sql2 = "UPDATE managers SET draft_order = '".$old_place."' WHERE draft_id = ".$draft_id." AND manager_id = ".$swap_manager_row['manager_id'];
		$manager_success = mysql_query($sql1);
		$swap_success = mysql_query($sql2);

		if(!$manager_success || !$swap_success) {
		$title = "Draft Order Not Updated";
		$msg = "An error occurred and the draft order wasn't updated.  Please go back and try again.<br/><br/>" . $sql1 . "<br/><br/>" . $sql2;
		require('views/error_page.php');
		exit(1);
		}else {
		header('Location: comm_manage_draft.php?did='.$draft_id);
		exit(0);
		}
		// </editor-fold>
		break;
}
?>
