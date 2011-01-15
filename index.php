<?php
session_start();

require_once('dbconn.php');
set_conn();

require_once('models/index_model.php');

$drafts = new indexObject();
$drafts->set_drafts();

require_once('/views/index_view.php');
?>