<?php
session_start();

require_once('dbconn.php');
set_conn();

require_once('models/index_model.php');
require_once('models/draft_object.php');

$drafts = new indexObject();
$drafts->draft_objects = draft_object::getAllDrafts();
$drafts->number_of_drafts = count($drafts->draft_objects);

switch($_GET['q']) {
	case 'select':
		DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
		require_once('/views/index/index_select_view.php');
		break;

	case 'index':
	default:
		DEFINE("ACTIVE_TAB", "INDEX");
		require_once('/views/index/index_view.php');
		break;
}

?>