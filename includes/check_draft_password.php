<?php

session_start();  //Start/restore user session data

require_once('/models/draft_object.php');
require_once('/models/user_object.php');

$_SESSION['draft_id'] = (int)$_SESSION['draft_id'];
$_SESSION['draft_password'] = $_SESSION['draft_password'];
$DRAFT_ID = (int)$_REQUEST['did'];

$pageURL = "http://";
$pageURL .= $_SERVER["SERVER_PORT"] != "80" ? $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

$DESTINATION = $pageURL;

$DRAFT = new draft_object($DRAFT_ID);

if($DRAFT->isPasswordProtected()) {
	if(!$DRAFT->checkDraftPublicLogin()) {
		require("/views/shared/draft_login.php");
		exit(0);
	}
}
//If we have gotten to this point, the user is properly logged in and we can continue.
?>