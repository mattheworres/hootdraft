<?php
require_once("check_login.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_GET['action']);

//NOTE: This uses _REQUEST because we grab the ID from both POSTs and GETs
DEFINE('MANAGER_ID', (int)$_REQUEST['mid']);

$MANAGER = new manager_object(MANAGER_ID);

// <editor-fold defaultstate="collapsed" desc="Error-checking on basic input">
if($MANAGER->manager_id == 0) {
	define("PAGE_HEADER", "Manager Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the manager could not be loaded. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}
// </editor-fold>

switch(ACTION) {
	case 'moveManager':
		// <editor-fold defaultstate="collapsed" desc="moveManager Logic">
		$direction = $_POST['direction'];
		
		switch($direction) {
			case 'up':
				$success = $MANAGER->moveManagerUp();
				break;
			
			case 'down':
				$success = $MANAGER->moveManagerDown();
				break;
		}	
		
		echo $success ? "SUCCESS" : "FAILURE";
		// </editor-fold>
		break;
	
	case 'editManager':
		require_once('/views/manager/edit_manager.php');
		break;
	
	case 'updateManager':
		// <editor-fold defaultstate="collapsed" desc="updateManager Logic">
		$ERRORS = array();
		$MANAGER->manager_name = trim($_POST['manager_name']);
		$MANAGER->team_name = trim($_POST['team_name']);
		
		$object_errors = $MANAGER->getValidity();
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once('/views/manager/edit_manager.php');
			exit(1);
		}
		
		if($MANAGER->saveManager() === false) {
			$ERRORS[] = "The manager was unable to be updated, please try again.";
			require_once('/views/manager/edit_manager.php');
			exit(1);
		}
		
		define("PAGE_HEADER", $MANAGER->manager_name . " Successfully Updated!");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "<em>" . $MANAGER->manager_name . "</em> has been successfully updated!<br/><br/><a href=\"manager.php?action=editManager&mid=" . $MANAGER->manager_id . "\">Click here</a> to edit this manager again, or <a href=\"draft.php?did=" . $MANAGER->draft_id . "\">click here</a> to go back to managing your draft.");
		require_once("/views/generic_result_view.php");
		// </editor-fold>
		break;
	
	case 'deleteManager':
		$success = $MANAGER->deleteManager();
		
		echo $success ? "SUCCESS" : "FAILURE";
		break;
}
?>