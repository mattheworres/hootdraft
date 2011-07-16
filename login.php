<?php session_start();	/*Even if we have a false alarm, start the session at least before headers are sent*/

include_once("dbconn.php");
set_conn();

require_once('models/login_model.php');

$login = new loginObject();

$status = $login->get_login_status();

switch($status) {
	case "SHOW_FIRST_FORM":
		define("ACTIVE_TAB", "LOGIN");
		require_once('/views/login_view.php');
		break;

	case "ALREADY_LOGGED_IN":
		header('Location: control_panel.php?action=home');
		echo "You're already logged in, but you should be getting forwarded to <a href=\"control_panel.php?action=home\">this page</a>.";
		break;

	case "INCORRECT_CREDENTIALS":
		define("ACTIVE_TAB", "LOGIN");
		define("LOGIN_ERROR", "INCORRECT_CREDENTIALS");
		require_once("/views/login_view.php");
		break;

	case "AUTHENTICATE_USER":
		$authenticated = $login->authenticate_user($_POST['txt_user'], $_POST['txt_pass']);

		if($authenticated) {
			header('Location: control_panel.php?action=home');
			exit(0);
		}else{
			define("ACTIVE_TAB", "LOGIN");
			define("LOGIN_ERROR", "DB_NO_MATCH");
			require_once('/views/login_view.php');
		}
		break;
}
?>