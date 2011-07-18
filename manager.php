<?php
require_once("check_login.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

DEFINE('MANAGER_ID', intval($_GET['mid']));

switch($_GET['action']) {
	case 'moveManager':
		// <editor-fold defaultstate="collapsed" desc="moveManager Logic">
		$manager_id = $_POST['mid'];
		$direction = $_POST['direction'];
		
		switch($direction) {
			case 'up':
				$success = manager_object::moveManagerUp($manager_id);
				break;
			
			case 'down':
				$success = manager_object::moveManagerDown($manager_id);
				break;
		}	
		
		echo $success ? "SUCCESS" : "FAILURE";
		// </editor-fold>
		break;
	
	case 'editManager':
		//TODO: Implement the view/error screen here
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
