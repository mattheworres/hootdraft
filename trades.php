<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE("DRAFT_ID", isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : 0);
DEFINE("TRADE_ID", isset($_REQUEST['tid']) ? (int)$_REQUEST['tid'] : 0);

$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("views/shared/generic_result_view.php");
	exit(1);
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Ensure Draft is Draftable">
if($DRAFT->isUndrafted()) {
	define("PAGE_HEADER", "Draft Not Ready For Picks Yet!");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "Your draft is currently set to \"Setting Up \", which means that the draft room isn't officially open yet. Once you've set your draft to \"In Progress\", come back here and you can begin entering picks for your draft.");
	require_once("views/shared/generic_result_view.php");
	exit(0);
}

if($DRAFT->isCompleted()) {
	define("PAGE_HEADER", "Draft Room is Closed - Your Draft is Over!");
	define("P_CLASS", "success");
	define("PAGE_CONTENT", "The draft room is officially closed.  Your draft is officially complete, all picks have been made and now all you have to do is get the results entered into your league.<br /><br /><a href=\"public_draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken to the draft's summary page, or <a href=\"public_draft.php?action=draftBoard&did=" . DRAFT_ID . "\">take a look at the draft board</a> to see all of the picks.");
	require_once("views/shared/generic_result_view.php");
	exit(0);
}
// </editor-fold>

switch(ACTION) {
	case 'getManagerPlayers':
		
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="Index Logic">
		$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
		require("views/trades/index.php");
		exit(0);
		// </editor-fold>
		break;
}
?>
