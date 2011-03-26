<?php
include_once("cleanstring.php");

class loginObject {
    public function get_login_status() {
        $user_id = CleanString($_SESSION['userid']);
        $user_name = CleanString($_SESSION['username']);
        $password = CleanString($_SESSION['password']);

        if(strlen($user_id) && strlen($user_name) && strlen($password))
        {
            $user_result = mysql_query("SELECT UserID
                                        FROM user_login
                                        WHERE
                                        UserID = '" . $user_id . "' AND
                                        UserName = '" . $user_name . "' AND
                                        Password = '" . $password . "'
                                       ");

            if(mysql_fetch_array($user_result)) {//If we did find a user that matched all of those credentials, we need to forward them to control panel
                $action = "ALREADY_LOGGED_IN";
            }else{
                $action = "INCORRECT_CREDENTIALS";
            }
        }elseif($_REQUEST['q'] != 1 || (!isset($_REQUEST['txt_user']) || !isset($_REQUEST['txt_pass']))) //If we haven't submitted a form, then we must show the initial form
        {
            $action = "SHOW_FIRST_FORM";
        }else
            $action = "AUTHENTICATE_USER";

        return $action;
    }

    public function authenticate_user($raw_username, $raw_password) {
        $username = CleanString($raw_username);
        $password = CleanString($raw_password);

        $user_result = mysql_query("SELECT UserID, Username, Password
                                    FROM user_login
                                    WHERE Username = '" . $username . "' AND
                                    Password = '" . sha1($password) . "'
                               ");
        if(!$user_row = mysql_fetch_array($user_result)) {
            return false;
        }else{
            $_SESSION['userid'] = $user_row['UserID'];
            $_SESSION['username'] = $user_row['Username'];
            $_SESSION['password'] = $user_row['Password'];
            return true;
        }
    }
}
?>