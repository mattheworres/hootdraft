<?php
require("includes/global_setup.php");

require('includes/check_draft_password.php');

DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE('DRAFT_ID', isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : 0);
DEFINE("BOARD_RELOAD", 5);

//Draft password may have pre-loaded this for us.
if(!isset($DRAFT) || get_class($DRAFT) != "draft_object")
	$DRAFT = new draft_object(DRAFT_ID);

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT === false || $DRAFT->draft_id == 0) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("views/shared/generic_result_view.php");
	exit(1);
}
// </editor-fold>

if(ACTION != 'isDraftReady' && $DRAFT->isUndrafted()) {
	$LAST_TEN_PICKS = $DRAFT->getLastTenPicks();
	$CURRENT_PICK = $DRAFT->getCurrentPick();
	require("views/public_draft/index.php");
	exit(0);
}

$DRAFT->setupSport();

switch(ACTION) {
	case 'isDraftReady':
		// <editor-fold defaultstate="collapsed" desc="isDraftReady Logic">
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		
		$json_object = array("IsDraftReady" => !$DRAFT->isUndrafted());
		echo json_encode($json_object);
		exit(0);
		// </editor-fold>
		break;
	
	case 'draftBoard':
		// <editor-fold defaultstate="collapsed" desc="draftBoard Logic">
		$MANAGERS = manager_object::getManagersByDraft(DRAFT_ID);
		$ALL_PICKS = $DRAFT->getAllDraftPicks();
		DEFINE("NUMBER_OF_MANAGERS", count($MANAGERS));
		DEFINE("COL_WIDTH", 115);
		DEFINE("TOTAL_WIDTH", 10 + COL_WIDTH * NUMBER_OF_MANAGERS);
		
		require("views/public_draft/draft_board.php");
		// </editor-fold>
		break;
	
	case 'checkPick':
		// <editor-fold defaultstate="collapsed" desc="checkPick Logic">
		//Ajax
		if($DRAFT->isCompleted())
			echo "9999";
		else
			echo (int)$DRAFT->draft_current_pick;
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
		
		require("views/public_draft/draft_board_naked.php");
		// </editor-fold>
		break;
	
	case 'picksPerManager':
		// <editor-fold defaultstate="collapsed" desc="picksPerManager Logic">
		$MANAGERS = manager_object::getManagersByDraft($DRAFT->draft_id);
		$MANAGER = $MANAGERS[0];
		$MANAGER_PICKS = player_object::getSelectedPlayersByManager($MANAGER->manager_id);
		$NOW = php_draft_library::getNowRefreshTime();
		require("views/public_draft/picks_per_manager.php");
		// </editor-fold>
		break;
	
	case 'loadManagerPicks':
		// <editor-fold defaultstate="collapsed" desc="loadManagerPicks Logic">
		$manager_id = (int)$_REQUEST['mid'];
		$MANAGER = new manager_object($manager_id);
		
		if($manager_id == 0 || $MANAGER === false) {
			exit(1);
		}
		
		$MANAGER_PICKS = player_object::getSelectedPlayersByManager($manager_id);
		$NOW = php_draft_library::getNowRefreshTime();
		
		if(empty($MANAGER_PICKS)) {
			echo "<h3>No picks for " . $MANAGER->manager_name . " yet.</h3>";
			exit(0);
		}
		
		require("views/public_draft/picks_per_manager_results.php");
		// </editor-fold>
		break;
	
	case 'picksPerRound':
		// <editor-fold defaultstate="collapsed" desc="picksPerRound Logic">
		$ROUND = 1;
		$ROUND_PICKS = player_object::getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
		$NOW = php_draft_library::getNowRefreshTime();
		require("views/public_draft/picks_per_round.php");
		// </editor-fold>
		break;
	
	case 'loadRoundPicks':
		// <editor-fold defaultstate="collapsed" desc="loadRoundPicks Logic">
		$ROUND = (int)$_REQUEST['round'];
		
		if($ROUND == 0)
			exit(1);
		
		$ROUND_PICKS = player_object::getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
		$NOW = php_draft_library::getNowRefreshTime();
		
		if(empty($ROUND_PICKS)) {
			echo "<h4>No draft selections have been made for round #" . $ROUND . " yet.</h4>";
			exit(0);
		}
		require("views/public_draft/picks_per_round_results.php");
		// </editor-fold>
		break;
		
	case 'searchDraft':
		// <editor-fold defaultstate="collapsed" desc="searchDraft Logic">
		require("views/public_draft/search_draft.php");
		// </editor-fold>
		break;
	
	case 'searchResults':
		// <editor-fold defaultstate="collapsed" desc="searchResults Logics">
		$team = isset($_GET['team']) ? $_GET['team'] : "";
		$position = isset($_GET['position']) ? $_GET['position'] : "";
		$keywords = isset($_GET['keywords']) ? $_GET['keywords'] : "";
		
		$SEARCHER = new search_object($keywords, $team, $position);
		$SEARCHER->searchDraft(DRAFT_ID);
		
		$NOW = php_draft_library::getNowRefreshTime();
		require("views/public_draft/search_draft_results.php");
		// </editor-fold>
		break;
	
	case 'viewTrades':
		// <editor-fold defaultstate="collapsed" desc="viewTrades Logic">
		$DRAFT_TRADES = trade_object::getDraftTrades(DRAFT_ID);
		DEFINE("NUMBER_OF_TRADES", count($DRAFT_TRADES));
		$DRAFT->setupSport();
		
		require("views/public_draft/draft_trades.php");
		// </editor-fold>
		break;
	
	case 'draftStats':
		// <editor-fold defaultstate="collapsed" desc="draftStats Logic">
		$STATS = new draft_statistics_object();
		$STATS->generateStatistics($DRAFT);
		$NOW = php_draft_library::getNowRefreshTime();
		require("views/public_draft/draft_statistics.php");
		// </editor-fold>
		break;
	
	case 'loadStats':
		// <editor-fold defaultstate="collapsed" desc="loadStats Logic">
		$STATS = new draft_statistics_object();
		$STATS->generateStatistics($DRAFT);
		$NOW = php_draft_library::getNowRefreshTime();
		require("views/public_draft/draft_statistics_results.php");
		// </editor-fold>
		break;
	
	default:
		// <editor-fold defaultstate="collapsed" desc="index logic">
		$LAST_TEN_PICKS = $DRAFT->getLastTenPicks();
		$CURRENT_PICK = $DRAFT->getCurrentPick();
		require("views/public_draft/index.php");
		// </editor-fold>
		break;
}
?>