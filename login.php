<?php 
require("includes/global_setup.php");

require_once('models/login_model.php');
define("ACTIVE_TAB", "LOGIN");
$login = new loginObject();

$status = $login->get_login_status();

switch($status) {
	case "SHOW_FIRST_FORM":
		
		require_once('views/login/login_view.php');
		break;

	case "ALREADY_LOGGED_IN":
		header('Location: control_panel.php?action=home');
		echo "You're already logged in, but you should be getting forwarded to <a href=\"control_panel.php?action=home\">this page</a>.";
		break;

	case "INCORRECT_CREDENTIALS":
		$ERRORS[] = "Your login is incorrect. Please login again.";
		require_once("views/login/login_view.php");
		break;

	case "AUTHENTICATE_USER":
		$authenticated = $login->authenticate_user($_POST['txt_user'], $_POST['txt_pass']);

		if($authenticated) {
			header('Location: control_panel.php?action=home');
			define("PAGE_HEADER", "You're Logged In!");
			define("P_CLASS", "success");
			define("PAGE_CONTENT", "You've been successfully authenticated. Unfortunately, your browser stopped the forward that was attempted.<br/><br/>Good news: You can <a href=\"control_panel.php?action=home\">click here</a> to be taken there right now. Kthxbai.");
			require_once("views/shared/generic_result_view.php");
			exit(0);
		}else{
			$ERRORS[] = "The username/password combination was incorrect. Please try again.";
			require_once('views/login/login_view.php');
		}
		break;
}
?>