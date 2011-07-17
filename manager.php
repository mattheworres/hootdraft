<?php
require_once("check_login.php");
require_once("models/manager_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

DEFINE('MANAGER_ID', intval($_GET['mid']));

switch($_GET['action']) {
	case 'moveManager':
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
		break;
}
?>
