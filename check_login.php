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
$_SESSION['userid'] = CleanString($_SESSION['userid']);
$_SESSION['username'] = CleanString($_SESSION['username']);
$_SESSION['password'] = CleanString($_SESSION['password']);

set_conn();	

//Default text to output if user is not logged in:
$login_text = "You are not properly logged in. You must <a href='login.php'>login</a> now.\n";

if(!isset($_SESSION['userid']) || !isset($_SESSION['username']) || !isset($_SESSION['password'])) {//If one or more of these aren't set, the user must now login.
    header('Location: login.php');
    echo $login_text;
    exit(1);
}

select_db("scsports_phpdraft");		//Select the database we wish to use

$user_result = mysql_query("SELECT UserID
							FROM user_login 
							WHERE 
							UserID = '" . $_SESSION['userid'] . "' AND
							UserName = '" . $_SESSION['username'] . "' AND
							Password = '" . $_SESSION['password'] . "'
						   ");

if(!$user_row = mysql_fetch_array($user_result)) {//If we didn't find a user that matched all of those credentials, we need to forward them to login
    header('Location: login.php');
    echo $login_text;
    exit(1);
}
//If we have gotten to this point, the user is properly logged in and we can continue.
?>