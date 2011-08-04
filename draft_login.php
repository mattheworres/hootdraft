<?php
//Going to treat this controller as a singleton - one action, then forwards back to whatever user was previously trying to do.
session_start();
require('/includes/dbconn.php');
set_conn();

require_once("models/draft_object.php");
DEFINE('ACTIVE_TAB', 'DRAFT_CENTRAL');

$DESTINATION = $_POST['destination'];
$DRAFT_ID = (int)$_POST['did'];
$DRAFT = new draft_object($DRAFT_ID);
$password = $_POST['draft_password'];

// <editor-fold defaultstate="collapsed" desc="Error checking on basic input">
if($DRAFT->draft_id == 0 || $DRAFT === false) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded. Please try again.");
	require_once("/views/generic_result_view.php");
	exit(1);
}
// </editor-fold>

if($DRAFT->draft_password != $password) {
	$ERRORS[] = "The password for the draft was incorrect, please try again.";
	require_once("/views/shared/draft_login.php");
	exit(1);
}

$_SESSION['did'] = $DRAFT_ID;
$_SESSION['draft_password'] = $password;

header('Location: ' . $DESTINATION);
echo "You've been forwarded to the draft page, but unfortunately your browser stopped you. <a href=\"" . $DESTINATION . "\">Click here</a> to continue on your merry way.";
exit(0);
?>
