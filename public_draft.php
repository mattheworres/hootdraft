<?php
require_once("dbconn.php");
set_conn();
require_once("/models/draft_object.php");

DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
DEFINE("ACTION", $_REQUEST['action']);
DEFINE('DRAFT_ID', intval($_REQUEST['did']));

$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT === false || $DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}
// </editor-fold>

$DRAFT->setupSport();

switch(ACTION) {
	
	default:
		$LAST_TEN_PICKS = $DRAFT->getLastTenPicks();
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		require("/views/public_draft/index.php");
		break;
}

?>
