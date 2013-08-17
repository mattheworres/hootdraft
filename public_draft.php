<?php

require("includes/global_setup.php");

require('includes/check_draft_password.php');

DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE('DRAFT_ID', isset($_REQUEST['did']) ? (int) $_REQUEST['did'] : 0);
DEFINE("CURRENT_COUNTER", isset($_REQUEST['currentCounter']) ? (int)$_REQUEST['currentCounter'] : 0);
DEFINE("BOARD_RELOAD", 3);

$DRAFT_SERVICE = new draft_service();
$MANAGER_SERVICE = new manager_service();
$PLAYER_SERVICE = new player_service();

//Draft password may have pre-loaded this for us.
if (!isset($DRAFT) || get_class($DRAFT) != "draft_object") {
  try {
    $DRAFT = $DRAFT_SERVICE->loadDraft(DRAFT_ID);
  } catch (Exception $e) {
    define("PAGE_HEADER", "Draft Not Found");
    define("P_CLASS", "error");
    define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded: " . $e->getMessage());
    require_once("views/shared/generic_result_view.php");
    exit(1);
  }
}

if ($DRAFT === false || $DRAFT->draft_id == 0) {
  define("PAGE_HEADER", "Draft Not Found");
  define("P_CLASS", "error");
  define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
  require_once("views/shared/generic_result_view.php");
  exit(1);
}

if(ACTION == 'isDraftReady') {
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
  header('Content-type: application/json');

  $json_object = array("IsDraftReady" => !$DRAFT->isUndrafted());
  echo json_encode($json_object);
  exit(0);
}

if($DRAFT->isUndrafted()) {
  switch(ACTION) {
    case 'refreshBoard':
      $response = array();
      $response["Status"] = "draft-not-ready";
      echo json_encode($response);
      exit(0);
      break;
    
    default:
      $LAST_TEN_PICKS = $PLAYER_SERVICE->getLastTenPicks($DRAFT->draft_id);
      
      if($DRAFT->isInProgress()) {
        $CURRENT_PICK = $PLAYER_SERVICE->getCurrentPick($DRAFT);
      }
      require("views/public_draft/index.php");
      exit(0);
      break;
  }
}

$DRAFT->setupSport();

switch (ACTION) {
  case 'draftBoard':
    // <editor-fold defaultstate="collapsed" desc="draftBoard Logic">
    $MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID);
    $ALL_PICKS = $DRAFT_SERVICE->getAllDraftPicks($DRAFT);
    DEFINE("NUMBER_OF_MANAGERS", count($MANAGERS));
    DEFINE("COL_WIDTH", 160); //Should be 160 - 2px for border on each side
    DEFINE("TOTAL_WIDTH", 85 + COL_WIDTH * NUMBER_OF_MANAGERS);

    require("views/public_draft/draft_board.php");
    // </editor-fold>
    break;

  case 'refreshBoard':
    $response = array();
    
    if(CURRENT_COUNTER == $DRAFT->draft_counter) {
      $response["Status"] = $DRAFT->isCompleted() ? "draft-complete" : "up-to-date";
      echo json_encode($response);
      exit(0);
    }
    
    $response["Status"] = $DRAFT->isCompleted() ? "draft-complete" : "out-of-date";
    //The hope here is to be a little more RESTful and if an exception is thrown the proper HTTP code will be thrown
    $response["Players"] = $PLAYER_SERVICE->getAllUpdatedPlayersForBoard($DRAFT, CURRENT_COUNTER);
    $response["CurrentCounter"] = $DRAFT->draft_counter;
    echo json_encode($response);
    break;

  case 'picksPerManager':
    // <editor-fold defaultstate="collapsed" desc="picksPerManager Logic">
    try {
      $MANAGERS = $MANAGER_SERVICE->getManagersByDraft($DRAFT->draft_id);
      $MANAGER = $MANAGERS[0];
      $MANAGER_PICKS = $PLAYER_SERVICE->getSelectedPlayersByManager($MANAGER->manager_id);
    } catch (Exception $e) {
      define("PAGE_HEADER", "Draft Not Found");
      define("P_CLASS", "error");
      define("PAGE_CONTENT", "Unable to load information: " . $e->getMessage() . " Please try again.");
      require_once("views/shared/generic_result_view.php");
      exit(1);
    }

    $NOW = php_draft_library::getNowRefreshTime();
    require("views/public_draft/picks_per_manager.php");
    // </editor-fold>
    break;

  case 'loadManagerPicks':
    // <editor-fold defaultstate="collapsed" desc="loadManagerPicks Logic">
    $MANAGER_SERVICE = new manager_service();
    $manager_id = (int) $_REQUEST['mid'];
    $MANAGER = $MANAGER_SERVICE->loadManager($manager_id);

    if ($manager_id == 0 || $MANAGER === false) {
      exit(1);
    }

    $MANAGER_PICKS = $PLAYER_SERVICE->getSelectedPlayersByManager($manager_id);
    $NOW = php_draft_library::getNowRefreshTime();

    if (empty($MANAGER_PICKS)) {
      echo "<h3>No picks for " . $MANAGER->manager_name . " yet.</h3>";
      exit(0);
    }

    require("views/public_draft/picks_per_manager_results.php");
    // </editor-fold>
    break;

  case 'picksPerRound':
    // <editor-fold defaultstate="collapsed" desc="picksPerRound Logic">
    $ROUND = 1;
    $ROUND_PICKS = $PLAYER_SERVICE->getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
    $NOW = php_draft_library::getNowRefreshTime();
    require("views/public_draft/picks_per_round.php");
    // </editor-fold>
    break;

  case 'loadRoundPicks':
    // <editor-fold defaultstate="collapsed" desc="loadRoundPicks Logic">
    $ROUND = (int) $_REQUEST['round'];

    if ($ROUND == 0)
      exit(1);

    $ROUND_PICKS = $PLAYER_SERVICE->getSelectedPlayersByRound($DRAFT->draft_id, $ROUND);
    $NOW = php_draft_library::getNowRefreshTime();

    if (empty($ROUND_PICKS)) {
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
    $LAST_TEN_PICKS = $PLAYER_SERVICE->getLastTenPicks($DRAFT->draft_id);
    $CURRENT_PICK = $PLAYER_SERVICE->getCurrentPick($DRAFT);
    require("views/public_draft/index.php");
    // </editor-fold>
    break;
}
?>