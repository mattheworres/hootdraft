<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE("DRAFT_ID", isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : 0);
DEFINE("TRADE_ID", isset($_REQUEST['tid']) ? (int)$_REQUEST['tid'] : 0);
DEFINE("MANAGER_ID", isset($_REQUEST['mid']) ? (int)$_REQUEST['mid'] : 0);

$DRAFT_SERVICE = new draft_service(); /*@var $DRAFT_SERVICE draft_service */
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

switch(ACTION) {
	case 'getManagerPlayers':
		// <editor-fold defaultstate="collapsed" desc="getManagerPlayers Logic">
		if(MANAGER_ID == 0) {
			echo "FAILURE";
			exit(1);
		}
		
		$players = $PLAYER_SERVICE->getAllPlayersByManager(MANAGER_ID, true);
		
		if(isset($players) && count($players) > 0) {
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json');
			echo json_encode($players);
			exit(0);
		}else {
			echo "FAILURE";
			exit(1);
		}
		// </editor-fold>
		break;
		
	case 'submitTrade':
		// <editor-fold defaultstate="collapsed" desc="submitTrade Logic">
		global $DBH; /* @var $DBH PDO */
		$manager1_id = isset($_POST['manager1']) ? (int)$_POST['manager1'] : 0;
		$manager2_id = isset($_POST['manager2']) ? (int)$_POST['manager2'] : 0;
		$manager1_assets = isset($_POST['manager1assets']) ? $_POST['manager1assets'] : array();
		$manager2_assets = isset($_POST['manager2assets']) ? $_POST['manager2assets'] : array();
		
		if($manager1_id == 0 || $manager2_id == 0 || empty($manager1_assets) || empty($manager2_assets)) {
			echo "FAILURE";
			exit(1);
		}
		
		//TODO: Find a way to make this shorter in presenter:
		$newTrade = trade_object::BuildTrade(DRAFT_ID, $manager1_id, $manager2_id, $manager1_assets, $manager2_assets);
		
		$object_errors = $newTrade->getValidity();
		
		if(count($object_errors) > 0) {
			echo json_encode($object_errors);
			exit(1);
		}
		
		if($newTrade->saveTrade($DRAFT) === false) {
			$save_errors = array();
			$save_errors[] = "Encountered an error when saving trade.";
			echo json_encode($save_errors);
			exit(1);
		}
    
    try {
      $DRAFT_SERVICE->incrementDraftCounter($DRAFT);
    }catch(Exception $e) {
      $save_errors = array();
			$save_errors[] = "Encountered an error when saving trade - unable to increment draft counter";
			echo json_encode($save_errors);
			exit(1);
    }
		
		echo "SUCCESS";
		exit(0);
		// </editor-fold>
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="Index Logic">
		$MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID);
		require("views/trades/index.php");
		exit(0);
		// </editor-fold>
		break;
}
?>
