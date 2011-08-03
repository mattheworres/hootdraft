<?php
include_once("cleanstring.php");
include_once("models/user_object.php");

class loginObject {
	public function get_login_status() {
		$userObject = new user_object();
		$userObject->getCurrentlyLoggedInUser();

		if(strlen($userObject->user_id) && strlen($userObject->user_name) && strlen($userObject->password))
		{
			$user_result = mysql_query("SELECT UserID
										FROM user_login
										WHERE
										UserID = " . (int)$userObject->user_id . " AND
										UserName = '" . mysql_real_escape_string($userObject->user_name) . "' AND
										Password = '" . mysql_real_escape_string($userObject->password) . "'");

			if(mysql_fetch_array($user_result)) {//If we did find a user that matched all of those credentials, we need to forward them to control panel
				$action = "ALREADY_LOGGED_IN";
			}else{
				$action = "INCORRECT_CREDENTIALS";
			}
		}elseif($_GET['q'] != 1 || (!isset($_POST['txt_user']) || !isset($_POST['txt_pass']))) //If we haven't submitted a form, then we must show the initial form
		{
			$action = "SHOW_FIRST_FORM";
		}else
			$action = "AUTHENTICATE_USER";

		return $action;
	}

	public function authenticate_user($raw_username, $raw_password) {
		$userObject = new user_object();
		$userObject->user_name = $raw_username;
		$userObject->password = user_object::getHashedPassword($raw_password);

		$user_result = mysql_query("SELECT UserID, Username, Password
									FROM user_login
									WHERE Username = '" . mysql_real_escape_string($userObject->user_name) . "' AND
									Password = '" . mysql_real_escape_string($userObject->password) . "'
							   ");
		if(!$user_row = mysql_fetch_array($user_result)) {
			return false;
		}else{
			$userObject->user_id = $user_row['UserID'];
			$userObject->user_name = $user_row['Username'];
			$userObject->password = $user_row['Password'];
			$userObject->updateAuthentication();
			return true;
		}
	}
}
?>