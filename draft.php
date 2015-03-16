<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE("DRAFT_ID", isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : "");

$DRAFT_SERVICE = new draft_service();
$MANAGER_SERVICE = new manager_service();

try {
	$DRAFT = $DRAFT_SERVICE->loadDraft(DRAFT_ID);
}catch(Exception $e) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded: " . $e->getMessage());
	require_once("views/shared/generic_result_view.php");
	exit(1);
}

switch(ACTION) {
	case 'addManagers':
		// <editor-fold defaultstate="collapsed" desc="addManagers Logic">
		$MANAGERS = array();
		$MANAGERS[] = new manager_object();

		$CURRENT_MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID, true);
		require_once('views/draft/add_managers.php');
		// </editor-fold>
		break;

	case 'saveManagers':
		// <editor-fold defaultstate="collapsed" desc="saveManagers Logic">
		$managers = isset($_POST['managers']) ? $_POST['managers'] : "";
		
		foreach($managers as $manager_request) {
			$new_manager = new manager_object();
			$new_manager->draft_id = DRAFT_ID;
			$new_manager->manager_name = $manager_request['manager_name'];
			$new_manager->manager_email = $manager_request['manager_email'];
			
			$object_errors = $MANAGER_SERVICE->getValidity($new_manager);
			
			if(count($object_errors) > 0) {
				$ERRORS = $object_errors;
				echo "SERVER_ERROR: " . $ERRORS;
				exit(1);
			}

			try {
				$MANAGER_SERVICE->saveManager($new_manager);
			}catch(Exception $e) {
				echo "SERVER_ERROR: " . $e->getMessage();
				exit(1);
			}
		}

		echo "SUCCESS";
		// </editor-fold>
		break;

	case 'updateVisibility':
		// <editor-fold defaultstate="collapsed" desc="updateVisibility Logic">
		$new_password = isset($_POST['password']) ? $_POST['password'] : "";

		if($DRAFT->draft_password == $new_password) {
			echo "SUCCESS";
			exit(0);
		}

		$DRAFT->draft_password = $new_password;
		
		try{
			$DRAFT_SERVICE->saveDraft($DRAFT);
		}catch(Exception $e) {
			echo "FAILURE";
		}
		
		echo "SUCCESS";
		// </editor-fold>
		break;
    
  case 'updateStatus':
    $new_status = $_POST['draft_status'];
    $response = array();
    
    if($DRAFT->draft_status == $new_status) {
      $response["Status"] = "status-unchanged";
      echo json_encode($response);
      exit(0);
    }
    
    if(!draft_object::checkStatus($new_status)) {
      $response["Status"] = "invalid-status";
      echo json_encode($response);
      exit(0);
    }
    
    try {
			$DRAFT_SERVICE->updateStatus($DRAFT, $new_status);
		}catch(Exception $e) {
			$response["Status"] = "unable-to-update";
			$response["Error"] = "Draft's status could not be updated: " . $e->getMessage() . " Please try again.";
			echo json_encode($response);
			exit(1);
		}
    
    $response["Status"] = "status-updated";
    echo json_encode($response);
    break;

	case 'editDraft':
		// <editor-fold defaultstate="collapsed" desc="editDraft Logic">
		if($DRAFT->isCompleted() || $DRAFT->isInProgress()) {
			define("PAGE_HEADER", "You Cannot Edit This Draft");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Because this draft is either in progress or completed, you are unable to edit the details of this draft. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to go back to the draft\'s homepage.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		require_once("views/draft/edit_draft.php");
		// </editor-fold>
		break;

	case 'updateDraft':
		// <editor-fold defaultstate="collapsed" desc="updateDraft Logic">
		if($DRAFT->isCompleted() || $DRAFT->isInProgress()) {
			define("PAGE_HEADER", "You Cannot Edit This Draft");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Because this draft is either in progress or completed, you are unable to edit the details of this draft. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to go back to the draft\'s homepage.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		$draft_name = isset($_POST['draft_name']) ? trim($_POST['draft_name']) : "";
		$draft_sport = isset($_POST['draft_sport']) ? trim($_POST['draft_sport']) : "";
		$draft_style = isset($_POST['draft_style']) ? trim($_POST['draft_style']) : "";
		$draft_rounds = isset($_POST['draft_rounds']) ? (int)$_POST['draft_rounds'] : "";

		$DRAFT->draft_name = $draft_name;
		$DRAFT->draft_sport = $draft_sport;
		$DRAFT->draft_style = $draft_style;
		$DRAFT->draft_rounds = $draft_rounds;

		$object_errors = $DRAFT_SERVICE->getValidity($DRAFT);

		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once("views/draft/edit_draft.php");
			exit(1);
		}

		try{
			$DRAFT_SERVICE->saveDraft($DRAFT);
		}catch(Exception $e) {
			$ERRORS[] = "Draft could not be saved, please try again.";
			require_once("views/control_panel/create_draft.php");
			exit(1);
		}

		define("PAGE_HEADER", "Draft Edited Successfully!");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft " . $DRAFT->draft_name . " has been edited successfully. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the draft's homepage, or <a href=\"draft.php?action=editDraft&did=" . DRAFT_ID . "\">click here</a> to edit the draft again.");
		require_once("views/shared/generic_result_view.php");
		// </editor-fold>
		break;

	case 'deleteDraft':
		// <editor-fold defaultstate="collapsed" desc="deleteDraft">
		DEFINE("ANSWER", "schfourteenteen");
		require_once('views/draft/delete_draft.php');
		// </editor-fold>
		break;

	case 'confirmDelete':
		// <editor-fold defaultstate="collapsed" desc="confirmDelete Logic">
		$answer = isset($_POST['txt_answer']) ? (int)$_POST['txt_answer'] : 0;

		if($answer != 111) {
			DEFINE("ANSWER", "schfifty five");
			$ERRORS[] = "You failed the math problem. You basically suck at life.";
			require_once("views/draft/delete_draft.php");
			exit(1);
		}
		
		try {
			$DRAFT_SERVICE->deleteDraft($DRAFT);
		}catch(Exception $e) {
			define("PAGE_HEADER", "Draft Unable to Be Removed");
			define("P_CLASS", "error");
			define("PAGE_CONTENT", "Unable to remove draft: " . $e->getMessage() . " Please <a href=\"draft.php?action=deleteDraft&did=" . DRAFT_ID . "\">go back</a> and try again.");
			require_once("views/shared/generic_result_view.php");
			exit(1);
		}
		
		define("PAGE_HEADER", "Draft Removed Successfully");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft was successfully removed. <a href=\"control_panel.php\">Click here</a> to go back to the control panel.");
		require_once("views/shared/generic_result_view.php");
		exit(0);
		// </editor-fold>
		break;
		
	case 'searchProPlayers':
		// <editor-fold defaultstate="collapsed" desc="searchProPlayers Logic">
		global $PHPD; /* @var $PHPD PHPDRAFT */
		
		if(!$PHPD->useAutocomplete()) {
			exit(0);
		}

    $PRO_PLAYER_SERVICE = new pro_player_service();
		
		$league = trim($_GET['league']);
		
		$first = trim($_GET['first']);
		$last = trim($_GET['last']);
		$team = trim($_GET['team']);
		$position = trim($_GET['position']);
		
		$first = strlen($first) == 0 ? "NA" : $first;
		$last = strlen($last) == 0 ? "NA" : $last;
		$team = strlen($team) == 0 ? "NA" : $team;
		$position = strlen($position) == 0 ? "NA" : $position;
		
		$pro_players = $PRO_PLAYER_SERVICE->SearchPlayers($league, $first, $last, $team, $position);
		
		echo json_encode($pro_players);
		exit(0);
		break;
		// </editor-fold>

  case 'addRoundTimes':
    // <editor-fold defaultstate="collapsed" desc="addManagers Logic">
    $ROUND_TIME_SERVICE = new round_time_service();

    //First, get our draft's round times (if they exist).
    $round_times = $ROUND_TIME_SERVICE->getRoundTimes(DRAFT_ID);
    //If one or more exist, we've enabled them. This is also a bool for the object
    $ROUND_TIMES_ENABLED = count($round_times) > 0;
    //Isolate the first one since we'll use it.
    $first_round_time = $ROUND_TIMES_ENABLED ? $round_times[0] : new round_time_object();

    //Get the collection (even if it's just one) and assign it to the view variable
    $DYNAMIC_ROUND_TIMES = $round_times;
    //Assign the static round time to its view variable
    $STATIC_ROUND_TIME = $first_round_time;

    //Setup the properties for the bools
    $IS_STATIC_TIME = $ROUND_TIMES_ENABLED && $first_round_time->is_static_time;
    $DRAFT_ROUNDS = $DRAFT->draft_rounds;

    require_once('views/draft/add_round_times.php');
    // </editor-fold>
    break;

  case 'saveRoundTimes':
    // <editor-fold defaultstate="collapsed" desc="saveManagers Logic">
    $ROUND_TIME_SERVICE = new round_time_service();

    //First, we must remove all round times:
    $ROUND_TIME_SERVICE->removeRoundTimesByDraft(DRAFT_ID);

    //Then, determine if we need to check all round times coming to us:
    $roundTimeIsEnabled = isset($_POST['isRoundTimesEnabled']) ? $_POST['isRoundTimesEnabled'] : false;

    if($roundTimeIsEnabled) {
      $roundTimes = isset($_POST['roundTimes']) ? $_POST['roundTimes'] : "";
      foreach($roundTimes as $round_time_request) {
        $new_round_time = new round_time_object();
        $new_round_time->draft_id = DRAFT_ID;
        $new_round_time->is_static_time = $round_time_request['is_static_time'] == "true" ? 1 : 0;
        $new_round_time->draft_round = $new_round_time->is_static_time == 1 ? null : $round_time_request['draft_round'];
        $new_round_time->round_time_seconds = $round_time_request['round_time_seconds'];

        $object_errors = $ROUND_TIME_SERVICE->getValidity($new_round_time);

        if(count($object_errors) > 0) {
          $ERRORS = $object_errors;
          echo "SERVER_ERROR: " . $ERRORS;
          exit(1);
        }

        try {
          $ROUND_TIME_SERVICE->saveRoundTime($new_round_time);
        }catch(Exception $e) {
          echo "SERVER_ERROR: " . $e->getMessage();
          exit(1);
        }
      }
    }

    echo "SUCCESS";
    // </editor-fold>
    break;


	
	default:
		// <editor-fold defaultstate="collapsed" desc="Main Draft Page Logic">
		$MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID, true);

		DEFINE('NUMBER_OF_MANAGERS', count($MANAGERS));
		DEFINE('HAS_MANAGERS', NUMBER_OF_MANAGERS > 0);
		DEFINE('LOWEST_ORDER', NUMBER_OF_MANAGERS > 0 ? $MANAGERS[NUMBER_OF_MANAGERS - 1]->draft_order : 0);
		
		if($DRAFT->isInProgress() || $DRAFT->isCompleted()) {
			$DRAFT->setupSport();
      $PLAYER_SERVICE = new player_service();
			$LAST_TEN_PICKS = $PLAYER_SERVICE->getLastTenPicks(DRAFT_ID);
			DEFINE('NUMBER_OF_LAST_PICKS', count($LAST_TEN_PICKS));
			
			if($LAST_TEN_PICKS === false) {
				define("PAGE_HEADER", "Last 10 Picks Unable to be Loaded");
				define("P_CLASS", "error");
				define("PAGE_CONTENT", "An error has occurred and the last 10 picks of your draft were unable to be loaded. Please try again.");
				require_once("views/shared/generic_result_view.php");
				exit(1);
			}
		}

		require_once('views/draft/index.php');
		// </editor-fold>
		break;
}
?>