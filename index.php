<?php
require("includes/global_setup.php");

require_once('models/index_model.php');

$DRAFT_SERVICE = new draft_service();

$drafts = new indexObject();
$drafts->draft_objects = $DRAFT_SERVICE->getAllDrafts();
$drafts->number_of_drafts = count($drafts->draft_objects);
DEFINE("ACTION", isset($_GET['action']) ? $_GET['action'] : "");

switch(ACTION) {
	case 'select':
		DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
		require_once('views/index/index_select_view.php');
		break;

	case 'index':
	default:
		DEFINE("ACTIVE_TAB", "INDEX");
		require_once('views/index/index_view.php');
		break;
}

?>