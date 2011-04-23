<?php
require_once("check_login.php");
require_once("models/draft_object.php");

DEFINE("ACTIVE_TAB", "CONTROL_PANEL");

switch($_REQUEST['action']) {
    case '':
    case 'home':
    default:
        DEFINE(DRAFTS, draft_object::getAllDrafts());
        //TODO: Continue ripping out additional spaghetti in remaining part of view;
        require_once('/views/manage_draft/index.php');
        break;
}
?>
