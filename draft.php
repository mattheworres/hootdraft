<?php

require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_GET['action']);
DEFINE('DRAFT_ID', intval($_GET['did']));

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
		//TODO: Add view here for bulk-add of managers
		//TODO: Make page more friendly, change team name to email, use more JS and less static boxes
		break;
	
	case 'saveManagers':
		//TODO: Implement the saving of managers here, or spit out errors.
		//Cycle through and add each manager, then add to list of newly added managers
		//Will have to add a custom view for success so we can display a table of newly added managers.
		break;
	
	case 'updateVisibility':
		//TODO: Make this a jQuery dialog AJAX-based thing... no need for more views.
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