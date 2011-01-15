<?php
require('check_login.php');
include_once("dbconn.php");

set_conn();


$draft_id = intval($_REQUEST['did']);
$manager_id = intval($_REQUEST['mid']);

if(empty($draft_id)) {
    $title = "Draft Not Found";
    $msg = "The draft that you were attempting to remove a manager from was not found.  Please go back and try again.";
    require('views/error_page.php');
    exit(1);
}elseif(empty($manager_id)) {
    $title = "Manager Not Found";
    $msg = "The manager you were attempting to remove from the draft was not found.  Please go back and try again.";
    require('views/error_page.php');
    exit(1);
}else {
    $manager_row = mysql_fetch_array(mysql_query("SELECT draft_order FROM managers WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."'"));
    $old_order = intval($manager_row['draft_order']);

    $sql = "DELETE FROM managers WHERE draft_id = '".$draft_id."' AND manager_id = '".$manager_id."'";
    $success = mysql_query($sql);
    if($success) {
	$managers_result = mysql_query("SELECT manager_id FROM managers WHERE draft_id = '".$draft_id."' AND manager_id != '".$manager_id."' AND draft_order > ".$old_order." ORDER BY draft_order ASC");
	while($manager_row = mysql_fetch_array($managers_result)) {
	    $sql = "UPDATE managers SET draft_order = ".$old_order." WHERE manager_id = ".$manager_row['manager_id'];
	    mysql_query($sql);
	    $old_order++;
	}
	header('Location: comm_manage_draft.php?did='.$draft_id);
    } else {
	$title = "Manager Not Found";
	$msg = "The manager you were attempting to remove from the draft could not be deleted.  Please go back and try again.";
	require('views/error_page.php');
	exit(1);
    }
}
?>
