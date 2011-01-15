<?php
session_start();

require_once('dbconn.php');
set_conn();

require_once('libraries/index_library.php');

$drafts = new indexObject();
$drafts->set_drafts();

require_once('/views/index_view.php');
?>