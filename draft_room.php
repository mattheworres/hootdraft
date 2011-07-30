<?php
require_once("check_login.php");
require_once("models/draft_object.php");
require_once("models/player_object.php");
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


$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
$kooky_labels = array();
$kooky_labels[] = "On Deck: ";
$kooky_labels[] = "In the Hole: ";
$kooky_labels[] = "Still on the Bench: ";
$kooky_labels[] = "Grabbing a Gatorade: ";
$kooky_labels[] = "Sippin on that Sizzerb: ";

$DRAFT->setupSport();

switch(ACTION) {
	case 'addScreen':
		// <editor-fold defaultstate="collapsed" desc="addScreen Logic">
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		
		require("/views/draft_room/add_pick.php");
		// </editor-fold>
		break;
	
	case 'addPick':
		$submitted_pick = new player_object();
		$submitted_pick->draft_id = DRAFT_ID;
		$submitted_pick->player_id = intval($_POST['pid']);
		$submitted_pick->manager_id = intval($_POST['mid']);
		$submitted_pick->first_name = $_POST['first_name'];
		$submitted_pick->last_name = $_POST['last_name'];
		$submitted_pick->team = $_POST['team'];
		$submitted_pick->position = $_POST['position'];
		$submitted_pick->player_round = intval($_POST['player_round']);
		$submitted_pick->player_pick = intval($_POST['player_pick']);
		
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		$CURRENT_PICK = $submitted_pick;
		
		$object_errors = $submitted_pick->getValidity($DRAFT);
		
		if(count($object_errors) > 0) {
			
			$ERRORS = $object_errors;
			require("/views/draft_room/add_pick.php");
			exit(1);
		}
		
		$previous_pick = player_object::getLastPick($DRAFT);
		
		if($submitted_pick->savePlayer(true) === false) {
			$ERRORS[] = "Unable to update pick, please try again.";
			require("/views/draft_room/add_pick.php");
			exit(1);
		}
		
		if($submitted_pick->updatePickDuration($previous_pick, $DRAFT) === false) {
			$ERRORS[] = "Unable to update pick duration, your draft has become out of sync. Please see a PHPDraft administrator.";
			require("/views/draft_room/add_pick.php");
			exit(1);
		}
		
		$next_pick = $DRAFT->getNextPick();
		if($DRAFT->moveDraftForward($next_pick) === false) {
			define("PAGE_HEADER", "Draft Unable to be Moved Forward");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the draft was unable to be moved forward.");
			require_once("/views/generic_result_view.php");
			exit(1);
		}
		
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		
		$SUCCESSES[] = "Player <strong>" . $submitted_pick->casualName() . "</strong> was successfully drafted by " . $submitted_pick->manager_name;
		require_once("/views/draft_room/add_pick.php");
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
