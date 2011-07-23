<?php

require_once("check_login.php");
require_once("models/draft_object.php");
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

switch(ACTION) {
	case 'addManagers':
		// <editor-fold defaultstate="collapsed" desc="addManagers Logic">
		$MANAGERS = array();
		$MANAGERS[] = new manager_object();
		
		$CURRENT_MANAGERS = manager_object::getManagersByDraftId(DRAFT_ID, true);
		require_once('/views/draft/add_managers.php');
		// </editor-fold>
		break;
	
	case 'saveManagers':
		// <editor-fold defaultstate="collapsed" desc="saveManagers Logic">
		$managers = $_POST['managers'];
		foreach($managers as $manager_request) {
			$new_manager = new manager_object();
			$new_manager->draft_id = DRAFT_ID;
			$new_manager->manager_name = $manager_request['manager_name'];
			$new_manager->manager_email = $manager_request['manager_email'];
			
			if(!$new_manager->saveManager()) {
				return "SERVER_ERROR";
				exit(1);
			}
		}
		
		echo "SUCCESS";
		// </editor-fold>
		break;
	
	case 'updateVisibility':
		$new_password = mysql_real_escape_string($_POST['draft_password']);
		
		if($DRAFT->password == $new_password) {
			echo "SUCCESS";
			exit(0);
		}
		
		$DRAFT->password = $new_password;
		
		if($DRAFT->saveDraft())
			echo "SUCCESS";
		else
			echo "FAILURE";
		break;
	
	case 'changeStatus':
		//TODO: Add view here
		break;
	
	case 'updateStatus':
		//TODO: Add processing and error/success here. Generic success should suffice.
		break;
	
	case 'editDraft':
		//TODO: Add view here
		break;
	
	case 'saveDraft':
		//TODO: Add update logic here. Generic result views.
		break;
	
	case 'deleteDraft':
		//TODO: Add view here?  Not sure if this can be AJAX-based or not.
		break;
	
	case '':
	default:
		// <editor-fold defaultstate="collapsed" desc="Main Draft Page Logic">
		require_once("models/manager_object.php");
		
		$MANAGERS = manager_object::getManagersByDraftId(DRAFT_ID, true);
		
		DEFINE('NUMBER_OF_MANAGERS', count($MANAGERS));
		DEFINE('HAS_MANAGERS', NUMBER_OF_MANAGERS > 0);
		DEFINE('LOWEST_ORDER', $MANAGERS[NUMBER_OF_MANAGERS - 1]->draft_order);
		
		require_once('/views/draft/index.php');
		// </editor-fold>
		break;
} ?>