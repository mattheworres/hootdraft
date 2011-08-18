<?php

class loginObject {
	public static function get_login_status() {
		global $LOGGED_IN_USER;

		if($LOGGED_IN_USER->userAuthenticated() || ($LOGGED_IN_USER->user_id > 0 && strlen($LOGGED_IN_USER->user_name) > 0 && strlen($LOGGED_IN_USER->password) > 0))
		{
			global $DBH; /* @var $DBH PDO */
			$user_stmt = $DBH->prepare("SELECT UserID FROM user_login WHERE UserID = ? AND UserName = ? AND Password = ?");
			$user_stmt->bindParam(1, $LOGGED_IN_USER->user_id);
			$user_stmt->bindParam(2, $LOGGED_IN_USER->user_name);
			$user_stmt->bindParam(3, $LOGGED_IN_USER->password);

			if($user_stmt->execute()) {//If we did find a user that matched all of those credentials, we need to forward them to control panel
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

	public static function authenticate_user($raw_username, $raw_password) {
		global $LOGGED_IN_USER;
		$LOGGED_IN_USER->user_name = $raw_username;
		$LOGGED_IN_USER->password = user_object::getHashedPassword($raw_password);

		$user_result = mysql_query("SELECT UserID, Username, Password
									FROM user_login
									WHERE Username = '" . mysql_real_escape_string($LOGGED_IN_USER->user_name) . "' AND
									Password = '" . mysql_real_escape_string($LOGGED_IN_USER->password) . "'
							   ");
		if(!$user_row = mysql_fetch_array($user_result)) {
			return false;
		}else{
			$LOGGED_IN_USER->user_id = $user_row['UserID'];
			$LOGGED_IN_USER->user_name = $user_row['Username'];
			$LOGGED_IN_USER->password = $user_row['Password'];
			$LOGGED_IN_USER->updateAuthentication();
			return true;
		}
	}
}
?>