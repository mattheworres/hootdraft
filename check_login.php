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
require_once('models/user_object.php');

set_conn();

$userObject = new user_object();
$userObject->getCurrentlyLoggedInUser();

if(!$userObject->userAuthenticated()) {
    header('Location: login.php');
    echo "You are not properly logged in. You must <a href='login.php'>login</a> now.\n";
    exit(1);
}

//If we have gotten to this point, the user is properly logged in and we can continue.
?>