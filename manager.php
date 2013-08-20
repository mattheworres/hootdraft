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
    
  case 'updateManager':
    $RESPONSE = array();
    
    $manager_name = isset($_POST['manager_name']) ? trim($_POST['manager_name']) : "";
		$manager_email = isset($_POST['manager_email']) ? trim($_POST['manager_email']) : "";
		
		$MANAGER->manager_name = $manager_name;
		$MANAGER->manager_email = $manager_email;
		
		$object_errors = $MANAGER_SERVICE->getValidity($MANAGER);
		
		if(count($object_errors) > 0) {
			$RESPONSE["Status"] = "invalid-data";
      $RESPONSE["Errors"] = $object_errors;
      echo json_encode($RESPONSE);
			exit(1);
		}
		
		try {
			$MANAGER_SERVICE->saveManager($MANAGER);
		}catch(Exception $e) {
			$RESPONSE["Status"] = "unable-to-save";
      echo json_encode($RESPONSE);
			exit(1);
		}
		
		$RESPONSE["Status"] = "save-successful";
    echo json_encode($RESPONSE);
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