<?php

require_once("dbconn.php");
set_conn();

require("check_draft_password.php");

require_once("/models/draft_object.php");
require_once("/models/manager_object.php");

DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
DEFINE("ACTION", $_REQUEST['action']);
DEFINE('DRAFT_ID', intval($_REQUEST['did']));

//Draft password may have pre-loaded this for us.
if(!isset($DRAFT) || get_class($DRAFT) != "draft_object")
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
		// <editor-fold defaultstate="collapsed" desc="picksPerManager Logic">
		require_once("/libraries/php_draft_library.php");
		$MANAGERS = manager_object::getManagersByDraft($DRAFT->draft_id);
		$MANAGER = $MANAGERS[0];
		$MANAGER_PICKS = player_object::getSelectedPlayersByManager($MANAGER->manager_id);
		$NOW = php_draft_library::getNowRefreshTime();
		require("/views/public_draft/picks_per_manager.php");
		// </editor-fold>
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
			echo "<h3>No picks for " . $MANAGER->manager_name . " yet.</h3>";
			exit(0);
		}
		
		require("/views/public_draft/picks_per_manager_results.php");
		// </editor-fold>
		break;
	
	case 'picksPerRound':
		// <editor-fold defaultstate="collapsed" desc="picksPerRound Logic">
		require_once("/libraries/php_draft_library.php");
		$ROUND = 1;
		$ROUND_PICKS = player_object::getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
		$NOW = php_draft_library::getNowRefreshTime();
		require("/views/public_draft/picks_per_round.php");
		// </editor-fold>
		break;
	
	case 'loadRoundPicks':
		// <editor-fold defaultstate="collapsed" desc="loadRoundPicks Logic">
		$ROUND = intval($_REQUEST['round']);
		
		if($ROUND == 0)
			exit(1);
		
		$ROUND_PICKS = player_object::getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
		$NOW = php_draft_library::getNowRefreshTime();
		
		if(empty($ROUND_PICKS)) {
			echo "<h4>No draft selections have been made for round #" . $ROUND . " yet.</h4>";
			exit(0);
		}
		require("/views/public_draft/picks_per_round_results.php");
		// </editor-fold>
		break;
		
	case 'searchDraft':
		// <editor-fold defaultstate="collapsed" desc="searchDraft Logic">
		require("/views/public_draft/search_draft.php");
		// </editor-fold>
		break;
	
	case 'searchResults':
		// <editor-fold defaultstate="collapsed" desc="searchResults Logics">
		require_once("/libraries/php_draft_library.php");
		require_once("/models/search_object.php");
		$team = $_GET['team'];
		$position = $_GET['position'];
		$SEARCHER = new search_object($_GET['keywords'], $_GET['team'], $_GET['position']);
		$SEARCHER->searchDraft($DRAFT->draft_id);
		
		$NOW = php_draft_library::getNowRefreshTime();
		require("/views/public_draft/search_draft_results.php");
		// </editor-fold>
		break;
	
	case 'viewStats':
		//TODO: Add data modeling for stats object, and get stats.
		//Note: stats object can include its own SQL queries, it's all read-only anyways so no error-checking needed, there's no user input.
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
