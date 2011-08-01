<?php

session_start();  //Start/restore user session data

require_once('dbconn.php');
require_once('/models/draft_object.php');
require_once('/models/user_object.php');

$_SESSION['draft_id'] = intval($_SESSION['draft_id']);
$_SESSION['draft_password'] = $_SESSION['draft_password'];
$DRAFT_ID = intval($_REQUEST['did']);

$pageURL = "http://";
$pageURL .= $_SERVER["SERVER_PORT"] != "80" ? $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

$DESTINATION = $pageURL;

$DRAFT = new draft_object($DRAFT_ID);
if($DRAFT->draft_id == 0 || $DRAFT === false) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "The draft you were attempting to view was not found. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}

$blart = $DRAFT->isPasswordProtected();
$hart = $DRAFT->checkDraftPublicLogin();
$hartablart = $hart && $blart;

if($DRAFT->isPasswordProtected()) {
	if(!$DRAFT->checkDraftPublicLogin()) {
		require("/views/shared/draft_login.php");
		exit(0);
	}
}
//If we have gotten to this point, the user is properly logged in and we can continue.
?>