<?php
require_once("dbconn.php");
set_conn();

require_once("/models/draft_object.php");
require_once("/models/manager_object.php");

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

//TODO: Add checking for draft password!!

switch(ACTION) {
	case 'draftBoard':
		// <editor-fold defaultstate="collapsed" desc="draftBoard Logic">
		$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
		$ALL_PICKS = $DRAFT->getAllDraftPicks();
		DEFINE("NUMBER_OF_MANAGERS", count($MANAGERS));
		DEFINE("COL_WIDTH", 115);
		DEFINE("TOTAL_WIDTH", 10 + COL_WIDTH * NUMBER_OF_MANAGERS);
		
		require("/views/public_draft/draft_board.php");
		// </editor-fold>
		break;
	
	case 'checkPick':
		// <editor-fold defaultstate="collapsed" desc="checkPick Logic">
		//Ajax
		if($DRAFT->isCompleted())
			echo "9999";
		else
			echo intval($DRAFT->current_pick);
		// </editor-fold>
		break;
	
	case 'loadDraftBoard':
		// <editor-fold defaultstate="collapsed" desc="loadDraftBoard Logic">
		//Ajax
		$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
		$ALL_PICKS = $DRAFT->getAllDraftPicks();
		DEFINE("NUMBER_OF_MANAGERS", count($MANAGERS));
		DEFINE("COL_WIDTH", 115);
		DEFINE("TOTAL_WIDTH", 10 + COL_WIDTH * NUMBER_OF_MANAGERS);
		
		require("/views/public_draft/draft_board_naked.php");
		// </editor-fold>
		break;
	
	case 'picksPerManager':
		require_once("/libraries/php_draft_library.php");
		$MANAGERS = manager_object::getManagersByDraft($DRAFT->draft_id);
		$MANAGER = $MANAGERS[0];
		$MANAGER_PICKS = player_object::getSelectedPlayersByManager($MANAGER->manager_id);
		$NOW = php_draft_library::getNowRefreshTime();
		require("/views/public_draft/picks_per_manager.php");
		break;
	
	case 'loadManagerPicks':
		// <editor-fold defaultstate="collapsed" desc="loadManagerPicks Logic">
		$manager_id = intval($_REQUEST['mid']);
		$MANAGER = new manager_object($manager_id);
		
		if($manager_id == 0 || $manager === false) {
			exit(1);
		}
		
		$MANAGER_PICKS = player_object::getSelectedPlayersByManager($manager_id);
		$NOW = php_draft_library::getNowRefreshTime();
		
		if(empty($MANAGER_PICKS)) {
			exit(0);
		}
		
		require("/views/public_draft/picks_per_manager_results.php");
		// </editor-fold>
		break;
	
	case 'picksPerRound':
		
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="index logic">
		$LAST_TEN_PICKS = $DRAFT->getLastTenPicks();
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		require("/views/public_draft/index.php");
		// </editor-fold>
		break;
}

?>
