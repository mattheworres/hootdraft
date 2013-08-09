<?php
require("includes/global_setup.php");
require("includes/check_login.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", isset($_GET['action']) ? $_GET['action'] : "");

//NOTE: This uses _REQUEST because we grab the ID from both POSTs and GETs
DEFINE('MANAGER_ID', isset($_REQUEST['mid']) ? (int)$_REQUEST['mid'] : 0);
$MANAGER_SERVICE = new manager_service();

$MANAGER = $MANAGER_SERVICE->loadManager(MANAGER_ID);

// <editor-fold defaultstate="collapsed" desc="Error-checking on basic input">
if($MANAGER->manager_id == 0) {
	define("PAGE_HEADER", "Manager Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the manager could not be loaded. Please try again.");
	require_once("views/shared/generic_result_view.php");
	exit(1);
}
// </editor-fold>

switch(ACTION) {
	case 'moveManager':
		// <editor-fold defaultstate="collapsed" desc="moveManager Logic">
		$direction = isset($_POST['direction']) ? $_POST['direction'] : "";
		
		try {
			switch($direction) {
				case 'up':
					$MANAGER_SERVICE->moveManagerUp($MANAGER);
					break;

				case 'down':
					$MANAGER_SERVICE->moveManagerDown($MANAGER);
					break;
			}
		}catch(Exception $e) {
			echo "FAILURE";
		}
		
		echo "SUCCESS";
		// </editor-fold>
		break;
	
	case 'editManager':
		// <editor-fold defaultstate="collapsed" desc="editManager Logic">
		require_once('views/manager/edit_manager.php');
		// </editor-fold>
		break;
	
	case 'updateManager':
		// <editor-fold defaultstate="collapsed" desc="updateManager Logic">
		$ERRORS = array();
		
		$manager_name = isset($_POST['manager_name']) ? trim($_POST['manager_name']) : "";
		$manager_email = isset($_POST['manager_email']) ? trim($_POST['manager_email']) : "";
		
		$MANAGER->manager_name = $manager_name;
		$MANAGER->manager_email = $manager_email;
		
		$object_errors = $MANAGER_SERVICE->getValidity($MANAGER);
		
		if(count($object_errors) > 0) {
			$ERRORS = $object_errors;
			require_once('views/manager/edit_manager.php');
			exit(1);
		}
		
		if($MANAGER->saveManager() === false) {
			$ERRORS[] = "The manager was unable to be updated, please try again.";
			require_once('views/manager/edit_manager.php');
			exit(1);
		}
		
		define("PAGE_HEADER", $MANAGER->manager_name . " Successfully Updated!");
		define("P_CLASS", "success");
		define("PAGE_CONTENT", "<em>" . $MANAGER->manager_name . "</em> has been successfully updated!<br/><br/><a href=\"manager.php?action=editManager&mid=" . $MANAGER->manager_id . "\">Click here</a> to edit this manager again, or <a href=\"draft.php?did=" . $MANAGER->draft_id . "\">click here</a> to go back to managing your draft.");
		require_once("views/shared/generic_result_view.php");
		// </editor-fold>
		break;
	
	case 'deleteManager':
		// <editor-fold defaultstate="collapsed" desc="deleteManager Logic">
		try {
			$MANAGER_SERVICE->deleteManager($MANAGER);
		}catch(Exception $e) {
			echo "FAILURE";
			exit(1);
		}
		
		echo "SUCCESS";
		exit(1);
		// </editor-fold>
		break;
}
?>