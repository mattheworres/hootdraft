<?php session_start();	/*Even if we have a false alarm, start the session at least before headers are sent*/
include_once("dbconn.php");
include_once("cleanstring.php");
$_SESSION['userid'] = CleanString($_SESSION['userid']);
$_SESSION['username'] = CleanString($_SESSION['username']);
$_SESSION['password'] = CleanString($_SESSION['password']);

set_conn();

if(isset($_SESSION['userid']) && isset($_SESSION['username']) && isset($_SESSION['password']) && isset($_SESSION['security_code'])) {//If one or more of these are set, the user may already be.
    select_db("scsports_phpdraft");		//Select the database we wish to use

    $user_result = mysql_query("SELECT UserID
				FROM user_login
				WHERE 
				UserID = '" . $_SESSION['userid'] . "' AND
				UserName = '" . $_SESSION['username'] . "' AND
				Password = '" . $_SESSION['password'] . "'
			       ");

    if($user_row = mysql_fetch_array($user_result)) {//If we did find a user that matched all of those credentials, we need to forward them to control panel
	header('Location: ccp.php');
	echo "You're already logged in, but you should be getting forwarded to <a href=\"ccp.php\">this page</a>.";
	exit(0);
    }
}elseif($_REQUEST['q'] != 1 || (!isset($_REQUEST['txt_user']) || !isset($_REQUEST['txt_pass']))) //If we haven't submitted a form, then we must show the initial form
    $action = "show_first";
else {
    select_db("scsports_phpdraft");	//Select the database

    //Clean the form input of any malicious/troublesome characters
    $username = CleanString($_POST['txt_user']);
    $password = CleanString($_POST['txt_pass']);

    $user_result = mysql_query("SELECT UserID, Username, Password
				FROM user_login
				WHERE Username = '" . $username . "' AND
				Password = '" . sha1($password) . "'
			   ");

    if(!$user_row = mysql_fetch_array($user_result)) {//MySQL couldn't find the matching username/password, show the form with an error message
	$action = "db_no_match";
    }else {//We found a row and the user input data
	//Initialize user credential variables stored in session
	$_SESSION['userid'] = $user_row['UserID'];
	$_SESSION['username'] = $user_row['Username'];
	$_SESSION['password'] = $user_row['Password'];
	header('Location: ccp.php');
	exit(0);
    }
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('menu.php'); ?>
	    <div id="content">
		<?php //Create a single string variable to store the "login" form so we don't have to maintain several forms at once
//Notice all escape characters behind double-quote characters (")
		$login_form = "<h3>Please Authenticate</h3>
		<div class=\"featurebox_center\">
            	<form method=\"post\" action=\"login.php?q=1\">
                  <fieldset>
                  <legend>Enter your username and password below to continue</legend>
                    <p><label for=\"txt_user\" class=\"left\">Username:</label>
                       <input type=\"text\" name=\"txt_user\" id=\"txt_user\" class=\"field\" maxlength=\"16\" value=\"\" tabindex=\"1\" /></p>
                    <p><label for=\"txt_pass\" class=\"left\">Password:</label>
                       <input type=\"password\" name=\"txt_pass\" id=\"txt_pass\" class=\"field\" maxlength=\"16\" tabindex=\"2\" /></p>
                    <p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Login\" tabindex=\"4\" /></p>
                    </fieldset>
                </form>
            </div>";
		if($action === "show_first") {//If we haven't submitted a form, then we must show the initial form
		    echo $login_form;
		}elseif($action === "db_no_match")		//if($_REQUEST['q'] != 1 || (!isset...
		{
		    ?><p><strong>*The username/password combination was incorrect. Please try again.</strong></p>
		    <?php	echo $login_form;
		}?>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>