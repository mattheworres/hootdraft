<?php session_start();	/*Even if we have a false alarm, start the session at least before headers are sent*/
include_once("dbconn.php");
include_once("cleanstring.php");
$_REQUEST['q'] = intval($_REQUEST['q']);
$draft_id = intval($_REQUEST['draft_id']);
$draft_password = CleanString(trim($_REQUEST['draft_password']));

set_conn();
select_db("scsports_phpdraft");

$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."' LIMIT 1");
$draft_row = mysql_fetch_array($draft_result);

if(isset($_SESSION['draft_id']) && isset($_SESSION['draft_password'])) {//If one or more of these are set, the user may already be.
    if($draft_row['draft_password'] == $_SESSION['draft_password'] && $draft_row['draft_id'] == $_SESSION['draft_id']) {
	header('Location: draft_main.php?draft_id='.$draft_id);
	echo "You're already logged in, but you should be getting forwarded to <a href=\"draft_main.php?draft_id=".$draft_id."\">this page</a>.";
	exit(0);
    }else{
	$action = "db_no_match";
    }
}elseif($_REQUEST['q'] != 1) //If we haven't submitted a form, then we must show the initial form
    $action = "show_first";
elseif($_REQUEST['q'] == 1) {
    $draft_result = mysql_query("SELECT draft_id, draft_password
				FROM draft
				WHERE draft_id = '" . $draft_id . "' AND
				draft_password = '" . $draft_password . "'
			   ");

    if(!$draft_row = mysql_fetch_array($draft_result)) {//MySQL couldn't find the matching username/password, show the form with an error message
	$action = "db_no_match";
	$err_msg = "Password is incorrect.";
    }else {//We found a row and the user input data
	//Initialize user credential variables stored in session
	$_SESSION['draft_id'] = $draft_row['draft_id'];
	$_SESSION['draft_password'] = $draft_row['draft_password'];
	header('Location: draft_main.php?draft_id='.$draft_id);
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
		$login_form = "<h3>Please Enter Draft Password</h3>
		<div class=\"featurebox_center\">
            	<form method=\"post\" action=\"draft_login.php?q=1\">
                  <fieldset>
                  <legend>Enter your draft password below to continue</legend>
		    <input type=\"hidden\" name=\"draft_id\" value=\"".$draft_id."\" />
                    <p><label for=\"draft_password\" class=\"left\">Draft Password:</label>
                       <input type=\"password\" name=\"draft_password\" id=\"draft_password\" class=\"field\" tabindex=\"1\" autocomplete=\"off\" /></p>
                    <p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Login\" tabindex=\"2\" /></p>
                    </fieldset>
                </form>
            </div>";
		if($action === "show_first") {//If we haven't submitted a form, then we must show the initial form
		    echo $login_form;
		}elseif($action === "db_no_match")		//if($_REQUEST['q'] != 1 || (!isset...
		{
		    ?><p class="error">*The password was incorrect for this draft. Please try again.</p>
		    <?php	echo $login_form;
		}else
		    echo "Action wasn't set?";?>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>