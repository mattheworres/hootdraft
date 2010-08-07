<?php	//check_login.php
/*
	This document shall be included on any page that requires valid login credentials
	
	Note:
	This document MUST be included BEFORE any output on a page because of it's use of
	the header() and session_start() functions.
*/
session_start();		//Start/restore user session data

require_once('dbconn.php');
require_once('cleanstring.php');
require_once('login_fcns.php');
$_SESSION['draft_id'] = CleanString($_SESSION['draft_id']);
$_SESSION['draft_password'] = CleanString($_SESSION['draft_password']);
$draft_id = intval($_REQUEST['draft_id']);

set_conn();
select_db("scsports_phpdraft");

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."' LIMIT 1");
$draft_row = mysql_fetch_array($draft_result);

//Default text to output if user is not logged in:
$login_text = "This draft is password-protected.  You must <a href=\"draft_login.php?draft_id=".$draft_id."\">enter a password</a> to see this draft.";

if($draft_row['draft_password'] != '' && !isLoggedIn()) {
    //Need a password
    if(!isset($_SESSION['draft_id']) || !isset($_SESSION['draft_password'])) {//If one or more of these aren't set, the user must now login.
	header('Location: draft_login.php?draft_id='.$draft_id);
	echo $login_text;
	exit(1);
    }

    select_db("scsports_phpdraft");		//Select the database we wish to use

    if($draft_row['draft_password'] != $_SESSION['draft_password'] || $draft_row['draft_id'] != $_SESSION['draft_id']) {//If we didn't find a match of those credentials, we need to forward them to login
	header('Location: draft_login.php?draft_id='.$draft_id);
	echo $login_text;
	exit(1);
    }
}
//If we have gotten to this point, the user is properly logged in and we can continue.
?>