<?php
require_once("/includes/check_login.php");
require_once("models/draft_object.php");
require_once("models/player_object.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_REQUEST['action']);
DEFINE('DRAFT_ID', (int)$_REQUEST['did']);
DEFINE('PLAYER_ID', (int)$_REQUEST['pid']);

$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("/views/shared/generic_result_view.php");
	exit(1);
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Ensure Draft is Draftable">
if($DRAFT->isUndrafted()) {
	define("PAGE_HEADER", "Draft Not Ready For Picks Yet!");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "Your draft is currently set to \"Setting Up \", which means that the draft room isn't officially open yet. Once you've set your draft to \"In Progress\", come back here and you can begin entering picks for your draft.");
	require_once("/views/shared/generic_result_view.php");
	exit(0);
}elseif($DRAFT->isCompleted()) {
	define("PAGE_HEADER", "Draft Room is Closed - Your Draft is Over!");
	define("P_CLASS", "success");
	define("PAGE_CONTENT", "The draft room is officially closed.  Your draft is officially complete, all picks have been made and now all you have to do is get the results entered into your league.  Take a look at the draft board to see all of the picks.");
	require_once("/views/shared/generic_result_view.php");
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
		// <editor-fold defaultstate="collapsed" desc="addPick Logic">
		$submitted_pick = new player_object();
		$submitted_pick->draft_id = DRAFT_ID;
		$submitted_pick->player_id = (int)$_POST['pid'];
		$submitted_pick->manager_id = (int)$_POST['mid'];
		$submitted_pick->first_name = $_POST['first_name'];
		$submitted_pick->last_name = $_POST['last_name'];
		$submitted_pick->team = $_POST['team'];
		$submitted_pick->position = $_POST['position'];
		$submitted_pick->player_round = (int)$_POST['player_round'];
		$submitted_pick->player_pick = (int)$_POST['player_pick'];
		
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		$CURRENT_PICK = clone $submitted_pick;
		
		$object_errors = $submitted_pick->getValidity($DRAFT);
		
		if(count($object_errors) > 0) {
			
			$ERRORS = $object_errors;
			require("/views/draft_room/add_pick.php");
			exit(1);
		}
		
		$previous_pick = player_object::getLastPick($DRAFT);
		
		if($previous_pick === false || $submitted_pick->savePlayer(true) === false) {
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
			require_once("/views/shared/generic_result_view.php");
			exit(1);
		}
		
		$NEXT_FIVE_PICKS = $DRAFT->getNextFivePicks();
		$LAST_FIVE_PICKS = $DRAFT->getLastFivePicks();
		unset($CURRENT_PICK);
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		
		$SUCCESSES[] = "<em>" . $submitted_pick->casualName() . "</em> was successfully drafted with the #" . $submitted_pick->player_pick . " selection.";
		require_once("/views/draft_room/add_pick.php");
		// </editor-fold>
		break;
		
	case 'selectPickToEdit':
		// <editor-fold defaultstate="collapsed" desc="selectPickToEdit Logic">
		$ROUND_1_PICKS = player_object::getSelectedPlayersByRound($DRAFT->draft_id, 1);
		require("/views/draft_room/select_pick_to_edit.php");
		// </editor-fold>
		break;
	
	case 'getEditablePicks':
		// <editor-fold defaultstate="collapsed" desc="getEditablePicks Logic">
		$round_number = (int)$_POST['round'];
		if($round_number == 0) {
			echo "ERROR";
			exit(1);
		}
		
		$editable_picks = player_object::getSelectedPlayersByRound(DRAFT_ID, $round_number);
		
		if(empty($editable_picks)) {
			exit(0);
		}
		
		echo json_encode($editable_picks);
		// </editor-fold>
		break;
	
	case 'editScreen':
		// <editor-fold defaultstate="collapsed" desc="editScreen Logic">
		$EDIT_PLAYER = new player_object(PLAYER_ID);
		
		if($EDIT_PLAYER === false || PLAYER_ID == 0 || !$EDIT_PLAYER->hasBeenSelected() || !$EDIT_PLAYER->pickExists()) {
			define("PAGE_HEADER", "Player Unable to be Edited");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "The player you were attempting to edit is un-editable. This may be because the wrong information was passed in, or the fact that the player/pick you were attempting to edit hasn't been selected in your draft.");
			require_once("/views/shared/generic_result_view.php");
			exit(1);
		}
		
		require("/views/draft_room/edit_pick.php");
		// </editor-fold>
		break;
	
	case 'editPick':
		// <editor-fold defaultstate="collapsed" desc="editPick Logic">
		$EDIT_PLAYER = new player_object(PLAYER_ID);
		
		if($EDIT_PLAYER === false || PLAYER_ID == 0 || !$EDIT_PLAYER->hasBeenSelected() || !$EDIT_PLAYER->pickExists()) {
			define("PAGE_HEADER", "Player Unable to be Edited");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "The player you were attempting to edit is un-editable. This may be because the wrong information was passed in, or the fact that the player/pick you were attempting to edit hasn't been selected in your draft.");
			require_once("/views/shared/generic_result_view.php");
			exit(1);
		}
		
		$EDIT_PLAYER->manager_id = (int)$_POST['manager_id'];
		$EDIT_PLAYER->first_name = $_POST['first_name'];
		$EDIT_PLAYER->last_name = $_POST['last_name'];
		$EDIT_PLAYER->team = $_POST['team'];
		$EDIT_PLAYER->position = $_POST['position'];
		
		$object_errors = $EDIT_PLAYER->getValidity($DRAFT);
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require("/views/draft_room/edit_pick.php");
			exit(1);
		}
		
		if($EDIT_PLAYER->savePlayer() === false) {
			$ERRORS[] = "There was an error while saving the pick. Please try again.";
			require("/views/draft_room/edit_pick.php");
			exit(1);
		}
		
		define("PAGE_HEADER", "Pick Edited Successfully");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Pick #" . $EDIT_PLAYER->player_pick . " " . $EDIT_PLAYER->casualName() .  " was successfully edited.<br/><br/><a href=\"draft_room.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the main draft room, or <a href=\"draft_room.php?action=selectPickToEdit&did=" . DRAFT_ID . "\">click here</a> to go back to edit another draft pick.");
		require_once("/views/shared/generic_result_view.php");
		// </editor-fold>
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="Index Logic">
		require_once("models/player_object.php");
		$LAST_TEN_PICKS = player_object::getLastTenPicks(DRAFT_ID);
		$DRAFT->setupSport();
		
		if($LAST_TEN_PICKS === false) {
			define("PAGE_HEADER", "Last 10 Picks Unable to be Loaded");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "An error has occurred and the last 10 picks of your draft were unable to be loaded. Please try again.");
			require_once("/views/shared/generic_result_view.php");
			exit(1);
		}
		
		require("/views/draft_room/index.php");
		// </editor-fold>
		break;
}
?>
