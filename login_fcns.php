<?php
require_once("dbconn.php");
require_once("cleanstring.php");

/*
isLoggedIn will return TRUE if the user is properly authenticated, and FALSE if not.
*/
function isLoggedIn() {
    $_SESSION['userid'] = CleanString($_SESSION['userid']);
    $_SESSION['username'] = CleanString($_SESSION['username']);
    $_SESSION['password'] = CleanString($_SESSION['password']);

    if(!isset($_SESSION['userid']) || !isset($_SESSION['username']) || !isset($_SESSION['password'])) {//If one or more of these aren't set, the user isn't logged in
	return false;
    }

    		//Select the PHPDraft database

    $user_result = mysql_query("SELECT UserID
							FROM user_login 
							WHERE 
							UserID = '" . $_SESSION['userid'] . "' AND
							UserName = '" . $_SESSION['username'] . "' AND
							Password = '" . $_SESSION['password'] . "'
						   ");

    if(!$user_row = mysql_fetch_array($user_result)) {//If we didn't find a user that matched all of those credentials, we need to forward them to login
	return false;
    }
    //If we have gotten to this point, the user is properly logged in and we return TRUE.
    return true;
}
?>