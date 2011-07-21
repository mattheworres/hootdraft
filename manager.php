<?php
require_once("check_login.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");
DEFINE("ACTION", $_GET['action']);

//NOTE: This uses _REQUEST because we grab the ID from both POSTs and GETs
DEFINE('MANAGER_ID', intval($_REQUEST['mid']));

$MANAGER = new manager_object(MANAGER_ID);

// <editor-fold defaultstate="collapsed" desc="Error-checking on basic input">
if(!$MANAGER) {
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
		
		break;
	
	case 'updateManager':
		//TODO: Implement the manager save here
		break;
	
	case 'deleteManager':
		//TODO: Implement the confirm action screen here
		break;
	
	case 'removeManager':
		//TODO: Implement the manager delete here
		break;
	
	case 'addManagers':
		//TODO: Implement the bulk add-managers screen here
		break;
}
?>
