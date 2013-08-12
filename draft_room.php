<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE('DRAFT_ID', isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : 0);
DEFINE('PLAYER_ID', isset($_REQUEST['pid']) ? (int)$_REQUEST['pid'] : 0);

$DRAFT_SERVICE = new draft_service();
$MANAGER_SERVICE = new manager_service();
$PLAYER_SERVICE = new player_service();

try {
	$DRAFT = $DRAFT_SERVICE->loadDraft(DRAFT_ID);
}catch(Exception $e) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded: " . $e->getMessage());
	require_once("views/shared/generic_result_view.php");
	exit(1);
}

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

$MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID);
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
		try {
			$CURRENT_PICK = $PLAYER_SERVICE->getCurrentPick($DRAFT);
			$CURRENT_PICK_MANAGER = $MANAGER_SERVICE->loadManager($CURRENT_PICK->manager_id);

			$NEXT_FIVE_PICKS = $PLAYER_SERVICE->getNextFivePicks($DRAFT);
			$LAST_FIVE_PICKS = $PLAYER_SERVICE->getLastFivePicks($DRAFT);
		}catch(Exception $e) {
			define("PAGE_HEADER", "Unable To Load Information");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred: " . $e->getMessage() . " Please check the system and install before continuing.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		require("views/draft_room/add_pick.php");
		// </editor-fold>
		break;
	
	case 'addPick':
		// <editor-fold defaultstate="collapsed" desc="addPick Logic">
		$submitted_pick = new player_object();
		$submitted_pick->draft_id = DRAFT_ID;
		$submitted_pick->player_id = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
		$submitted_pick->manager_id = isset($_POST['mid']) ? (int)$_POST['mid'] : 0;
		$submitted_pick->first_name = isset($_POST['first_name']) ? $_POST['first_name'] : "";
		$submitted_pick->last_name = isset($_POST['last_name']) ? $_POST['last_name'] : "";
		$submitted_pick->team = isset($_POST['team']) ? $_POST['team'] : "";
		$submitted_pick->position = isset($_POST['position']) ? $_POST['position'] : "";
		$submitted_pick->player_round = isset($_POST['player_round']) ? (int)$_POST['player_round'] : 0;
		$submitted_pick->player_pick = isset($_POST['player_pick']) ? (int)$_POST['player_pick'] : 0;
		
		try {
			$NEXT_FIVE_PICKS = $PLAYER_SERVICE->getNextFivePicks($DRAFT);
			$LAST_FIVE_PICKS = $PLAYER_SERVICE->getLastFivePicks($DRAFT);
			$CURRENT_PICK = clone $submitted_pick;
		}catch(Exception $e) {
			define("PAGE_HEADER", "Unable To Add Pick");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the pick was unable to be added: " . $e->getMessage() . " Please check the system and install before continuing.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		$object_errors = $PLAYER_SERVICE->getValidity($DRAFT, $submitted_pick);
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require("views/draft_room/add_pick.php");
			exit(1);
		}
		
		$previous_pick = $PLAYER_SERVICE->getLastPick($DRAFT);
		
		//Fixes defect for a refresh POSTing already-added picks:
		if($previous_pick != null && $previous_pick->player_id == $submitted_pick->player_id) {
			$ERRORS[] = "Pick #" . $previous_pick->player_pick . " was already added, please enter the #" . $DRAFT->draft_current_pick . " pick now.";
			require("views/draft_room/add_pick.php");
			exit(1);
		}
		
		//Ensure future picks can't be selected (extra safety):
		if($previous_pick != null && $previous_pick->player_pick + 1 != $submitted_pick->player_pick) {
			$ERRORS[] = "Synchronization issue, you are attempting to enter a pick after an undrafted pick - unable to enter pick #" . $submitted_pick->player_pick . " at this moment. Try going back to the draft room, and re-entering this screen.";
			require("views/draft_room/add_pick.php");
			exit(1);
		}
		
		try {
			$PLAYER_SERVICE->savePlayer($submitted_pick, true);
		}catch(Exception $e) {
			$ERRORS[] = "Unable to update pick, please try again.";
			require("views/draft_room/add_pick.php");
			exit(1);
		}
		
		try {
			$PLAYER_SERVICE->updatePickDuration($submitted_pick, $previous_pick, $DRAFT);
		}catch(Exception $e) {
			$ERRORS[] = "Unable to update pick duration, your draft has become out of sync. Please see a PHPDraft administrator.";
			require("views/draft_room/add_pick.php");
			exit(1);
		}
		
		try {
			$next_pick = $PLAYER_SERVICE->getNextPick($DRAFT);
		}catch(Exception $e) {
			define("PAGE_HEADER", "Unable to Get Next Pick of Draft");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the next pick of the draft was unable to be loaded.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		try{
			$DRAFT_SERVICE->moveDraftForward($DRAFT, $next_pick);
		}catch(Exception $e) {
			define("PAGE_HEADER", "Draft Unable to be Moved Forward");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the draft was unable to be moved forward.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		if($DRAFT->isCompleted()) {
			define("PAGE_HEADER", "Draft Room is Closed - Your Draft is Over!");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "The draft room is officially closed.  Your draft is officially complete, all picks have been made and now all you have to do is get the results entered into your league.<br /><br /><a href=\"public_draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken to the draft's summary page, or <a href=\"public_draft.php?action=draftBoard&did=" . DRAFT_ID . "\">take a look at the draft board</a> to see all of the picks.");
			require_once("views/shared/generic_result_view.php");
			exit(0);
		}
		
		try {
			$NEXT_FIVE_PICKS = $PLAYER_SERVICE->getNextFivePicks($DRAFT);
			$LAST_FIVE_PICKS = $PLAYER_SERVICE->getLastFivePicks($DRAFT);
			
			unset($CURRENT_PICK);
			
			$CURRENT_PICK = $PLAYER_SERVICE->getCurrentPick($DRAFT);
			$CURRENT_PICK_MANAGER = $MANAGER_SERVICE->loadManager($CURRENT_PICK->manager_id);
		}catch(Exception $e) {
			define("PAGE_HEADER", "Unexpected Load Error Experienced");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and a piece of information was unable to be loaded: " . $e->getMessage() . " Please check to ensure everything is still operating correctly before moving forward.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		$SUCCESSES[] = "<em>" . $submitted_pick->casualName() . "</em> was successfully drafted with the #" . $submitted_pick->player_pick . " selection.";
		require_once("views/draft_room/add_pick.php");
		// </editor-fold>
		break;
		
	case 'selectPickToEdit':
		// <editor-fold defaultstate="collapsed" desc="selectPickToEdit Logic">
		$ROUND_1_PICKS = $PLAYER_SERVICE->getSelectedPlayersByRound($DRAFT->draft_id, 1);
		require("views/draft_room/select_pick_to_edit.php");
		// </editor-fold>
		break;
	
	case 'getEditablePicks':
		// <editor-fold defaultstate="collapsed" desc="getEditablePicks Logic">
		$round_number = isset($_POST['round']) ? (int)$_POST['round'] : 0;
		if($round_number == 0) {
			echo "ERROR";
			exit(1);
		}
		
		$editable_picks = $PLAYER_SERVICE->getSelectedPlayersByRound(DRAFT_ID, $round_number);
		
		if(empty($editable_picks)) {
			exit(0);
		}
		
		echo json_encode($editable_picks);
		// </editor-fold>
		break;
	
	case 'editScreen':
		// <editor-fold defaultstate="collapsed" desc="editScreen Logic">
		$EDIT_PLAYER = $PLAYER_SERVICE->loadPlayer(PLAYER_ID);
		$EDIT_PLAYER_MANAGER = $MANAGER_SERVICE->loadManager($EDIT_PLAYER->manager_id);
		
		if($EDIT_PLAYER === false || PLAYER_ID == 0 || !$EDIT_PLAYER->hasBeenSelected() || !$PLAYER_SERVICE->pickExists($EDIT_PLAYER)) {
			define("PAGE_HEADER", "Player Unable to be Edited");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "The player you were attempting to edit is un-editable. This may be because the wrong information was passed in, or the fact that the player/pick you were attempting to edit hasn't been selected in your draft.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		require("views/draft_room/edit_pick.php");
		// </editor-fold>
		break;
	
	case 'editPick':
		// <editor-fold defaultstate="collapsed" desc="editPick Logic">
		$EDIT_PLAYER = $PLAYER_SERVICE->loadPlayer(PLAYER_ID);
		
		if($EDIT_PLAYER === false || PLAYER_ID == 0 || !$EDIT_PLAYER->hasBeenSelected() || !$PLAYER_SERVICE->pickExists($EDIT_PLAYER)) {
			define("PAGE_HEADER", "Player Unable to be Edited");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "The player you were attempting to edit is un-editable. This may be because the wrong information was passed in, or the fact that the player/pick you were attempting to edit hasn't been selected in your draft.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		$EDIT_PLAYER->manager_id = isset($_POST['manager_id']) ? (int)$_POST['manager_id'] : 0;
		$EDIT_PLAYER->first_name = isset($_POST['first_name']) ? $_POST['first_name'] : "";
		$EDIT_PLAYER->last_name = isset($_POST['last_name']) ? $_POST['last_name'] : "";
		$EDIT_PLAYER->team = isset($_POST['team']) ? $_POST['team'] : "";
		$EDIT_PLAYER->position = isset($_POST['position']) ? $_POST['position'] : "";
		
		$object_errors = $PLAYER_SERVICE->getValidity($DRAFT, $EDIT_PLAYER);
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require("views/draft_room/edit_pick.php");
			exit(1);
		}
		
		try {
			$PLAYER_SERVICE->savePlayer($EDIT_PLAYER);
		}catch(Exception $e) {
			$ERRORS[] = "There was an error while saving the pick. Please try again.";
			require("views/draft_room/edit_pick.php");
			exit(1);
		}
		
		define("PAGE_HEADER", "Pick Edited Successfully");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Pick #" . $EDIT_PLAYER->player_pick . " " . $EDIT_PLAYER->casualName() .  " was successfully edited.<br/><br/><a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the main draft page, or <a href=\"draft_room.php?action=selectPickToEdit&did=" . DRAFT_ID . "\">click here</a> to go back to edit another draft pick.");
		require_once("views/shared/generic_result_view.php");
		// </editor-fold>
		break;
	
	case 'home':
	default:
		// <editor-fold defaultstate="collapsed" desc="Index Logic (now obsolete - shows error)">
		define("PAGE_HEADER", "Page No Longer Exists");
		define("P_CLASS", "error");
		define("PAGE_CONTENT", "This page no longer exists - functionality from this page can now be found on <a href=\"draft.php?did=" . DRAFT_ID . "\">the draft's main page</a>.");
		require_once("views/shared/generic_result_view.php");
		exit(1);
		// </editor-fold>
		break;
}
?>
