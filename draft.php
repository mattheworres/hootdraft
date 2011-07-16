<?php
require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

$draft_id = intval($_GET['did']);
DEFINE('DRAFT_ID', $draft_id);
$DRAFT = new draft_object();
$DRAFT->loadById(DRAFT_ID);

if($DRAFT === null || DRAFT_ID == 0) {
	define("PAGE_HEADER", "Draft Not Founds");
	define("PAGE_CONTENT", "<p class=\"error\">Your user account has been successfully updated. <a href=\"control_panel.php?action=manageProfile\">Click here</a> to change your profile again, or <a href=\"control_panel.php\">click here</a> to be taken back to the control panel.</p>");
	require_once("/views/generic_result_view.php");
}

switch($_GET['action']) {
	case '':
	default:
		//TODO: Rip into a manager object:
		$manager_result = mysql_query("SELECT * FROM managers WHERE draft_id = '" . DRAFT_ID . "' ORDER BY draft_order");
		$manager_num = mysql_num_rows($manager_result);

		//TODO: Probably shouldn't ping the DB again. Should cycle through managers we have in memory already for this information:
		$draft_order_result = mysql_query("SELECT draft_order FROM managers WHERE draft_id = '" . DRAFT_ID . "' ORDER BY draft_order DESC LIMIT 1");
		$draft_order_row = mysql_fetch_array($draft_order_result);
		$lowest_order = $draft_order_row['draft_order'];
		
		require_once('/views/control_panel/index.php');
		break;
}
?>
