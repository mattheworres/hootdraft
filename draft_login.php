<?php
//Going to treat this controller as a singleton - one action, then forwards back to whatever user was previously trying to do.
require("includes/global_setup.php");

DEFINE('ACTIVE_TAB', 'DRAFT_CENTRAL');

$DESTINATION = isset($_POST['destination']) ? $_POST['destination'] : "";
$DRAFT_ID = isset($_POST['did']) ? (int)$_POST['did'] : 0;
$DRAFT_SERVICE = new draft_service();

try {
	$DRAFT = $DRAFT_SERVICE->loadDraft(DRAFT_ID);
}catch(Exception $e) {
	define("PAGE_HEADER", "Draft Not Found");
	define("P_CLASS", "error");
	define("PAGE_CONTENT", "We're sorry, but the draft could not be loaded: " . $e->getMessage());
	require_once("views/shared/generic_result_view.php");
	exit(1);
}

$password = isset($_POST['draft_password']) ? $_POST['draft_password'] : "";

if($DRAFT->draft_password != $password) {
	$ERRORS[] = "The password for the draft was incorrect, please try again.";
	require_once("views/shared/draft_login.php");
	exit(1);
}

$_SESSION['did'] = $DRAFT_ID;
$_SESSION['draft_password'] = $password;

header('Location: ' . $DESTINATION);
echo "You've been forwarded to the draft page, but unfortunately your browser stopped you. <a href=\"" . $DESTINATION . "\">Click here</a> to continue on your merry way.";
exit(0);
?>
