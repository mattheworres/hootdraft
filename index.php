<?php
session_start();

require_once('dbconn.php');
set_conn();

require_once('models/index_model.php');

$drafts = new indexObject();
$drafts->set_drafts();

switch($_GET['q']) {
	case 'select':
		DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
		require_once('/views/index_select_view.php');
		break;

	case 'index':
	default:
		DEFINE("ACTIVE_TAB", "INDEX");
		require_once('/views/index_view.php');
		break;
}

?>