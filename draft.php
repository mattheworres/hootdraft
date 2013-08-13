<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");
DEFINE("DRAFT_ID", isset($_REQUEST['did']) ? (int)$_REQUEST['did'] : "");

$DRAFT_SERVICE = new draft_service();
$MANAGER_SERVICE = new manager_service();
$PLAYER_SERVICE = new player_service();
$PRO_PLAYER_SERVICE = new pro_player_service();

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

	case 'changeStatus':
		// <editor-fold defaultstate="collapsed" desc="changeStatus Logic">
		require_once("views/draft/edit_status.php");
		// </editor-fold>
		break;

	case 'updateStatus':
		// <editor-fold defaultstate="collapsed" desc="updateStatus Logic">
		$new_status = isset($_POST['draft_status']) ? $_POST['draft_status'] : "";

		if($DRAFT->draft_status == $new_status) {
			define("PAGE_HEADER", "Status Unchanged");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "Your draft's status was unchanged. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to the draft's main page, or <a href=\"draft.php?action=changeStatus&did=" . DRAFT_ID . "\">click here</a> to change it's status.");
			require_once("views/shared/generic_result_view.php");
			exit(0);
		}

		if(!draft_object::checkStatus($new_status)) {
			$ERRORS = array();
			$ERRORS[] = "Draft status is of the incorrect value. Please correct this and try again.";
			require_once("views/draft/edit_status.php");
			exit(1);
		}
		
		try {
			$DRAFT_SERVICE->updateStatus($DRAFT, $new_status);
		}catch(Exception $e) {
			$ERRORS = array();
			$ERRORS[] = "An error occurred and your draft's status could not be updated: " . $e->getMessage() . " Please try again.";
			require_once("views/draft/edit_status.php");
			exit(1);
		}
		
		define("PAGE_HEADER", "Draft Status Updated");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "Your draft's status has been successfully updated. <a href=\"draft.php?did=" . DRAFT_ID . "\">Click here</a> to be taken back to its main page.");
		require_once("views/shared/generic_result_view.php");
		exit(0);
		// </editor-fold>
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
	
	default:
		// <editor-fold defaultstate="collapsed" desc="Main Draft Page Logic">
		$MANAGERS = $MANAGER_SERVICE->getManagersByDraft(DRAFT_ID, true);

		DEFINE('NUMBER_OF_MANAGERS', count($MANAGERS));
		DEFINE('HAS_MANAGERS', NUMBER_OF_MANAGERS > 0);
		DEFINE('LOWEST_ORDER', NUMBER_OF_MANAGERS > 0 ? $MANAGERS[NUMBER_OF_MANAGERS - 1]->draft_order : 0);
		
		if($DRAFT->isInProgress() || $DRAFT->isCompleted()) {
			$DRAFT->setupSport();
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