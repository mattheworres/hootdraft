<?php
require_once("check_login.php");
require_once("models/draft_object.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_REQUEST['action']);
DEFINE('DRAFT_ID', intval($_REQUEST['did']));

$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Ensure Draft is Draftable">
if($DRAFT->isUndrafted()) {
	define("PAGE_HEADER", "Draft Not Ready For Picks Yet!");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "Your draft is currently set to \"Setting Up \", which means that the draft room isn't officially open yet. Once you've set your draft to \"In Progress\", come back here and you can begin entering picks for your draft.");
	require_once("/views/generic_result_view.php");
	exit(0);
}elseif($DRAFT->isCompleted()) {
	define("PAGE_HEADER", "Draft Room is Closed - Your Draft is Over!");
	define("P_CLASS", "success");
	define("PAGE_CONTENT", "The draft room is officially closed.  Your draft is officially complete, all picks have been made and now all you have to do is get the results entered into your league.  Take a look at the draft board to see all of the picks.");
	require_once("/views/generic_result_view.php");
	exit(0);
}
// </editor-fold>

switch(ACTION) {
	case 'addScreen':
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		$kooky_labels = array();
		$kooky_labels[] = "On Deck: ";
		$kooky_labels[] = "In the Hole: ";
		$kooky_labels[] = "Still on the Bench: ";
		$kooky_labels[] = "Grabbing a Gatorade: ";
		$kooky_labels[] = "Sippin on that Sizzerb: ";
		
		$DRAFT->setupSport();
		
		require("/views/draft_room/add_pick.php");
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="Index Logic">
		require_once("models/player_object.php");
		$LAST_TEN_PICKS = player_object::getLastTenPicks(DRAFT_ID);
		
		if($LAST_TEN_PICKS === false) {
			define("PAGE_HEADER", "Last 10 Picks Unable to be Loaded");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the last 10 picks of your draft were unable to be loaded. Please try again.");
			require_once("/views/generic_result_view.php");
			exit(1);
		}
		
		require("/views/draft_room/index.php");
		// </editor-fold>
		break;
}
?>
