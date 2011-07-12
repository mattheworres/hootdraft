<?php
require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

switch($_REQUEST['action']) {
    //THIS controller will be for when we have been given a single ID for a draft.
    //For the time being, I forsee it becoming pretty large, and almost unwieldy as all logic
    //for draft operations will live here.  We can split this out later, at least then it will be pretty manageable
    
    case 'manage':
        
        break;
    
    //TODO: Determine if sending them back to the "control panel" as a default is good behavior?
    case '':
    default:
        require_once('/views/control_panel/index.php');
        break;
}
?>
