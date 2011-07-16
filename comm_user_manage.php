<?php require('check_login.php');?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		function print_edit($username, $name) {
		echo "<fieldset>
			<legend>Update User</legend>
			<form action=\"comm_user_manage.php\" method=\"post\">
				<p><label for=\"username\">Login*:</label>
				<input type=\"text\" name=\"username\" id=\"username\" value=\"" . $username . "\" /></p>
				<p><label for=\"old_password\">Old Password:</label>
				<input type=\"password\" name=\"old_password\" id=\"old_password\" value=\"\" autocomplete=\"off\" /></p>
				<p><label for=\"old_password\">New Password:</label>
				<input type=\"password\" name=\"new_password\" id=\"new_password\" value=\"\" autocomplete=\"off\" /></p>
				<p><label for=\"old_password\">New Password (verify):</label>
				<input type=\"password\" name=\"verify_password\" id=\"verify_password\" value=\"\" autocomplete=\"off\" /></p>
				<p><label for=\"name\">Your Public Name*:</label>
				<input type=\"text\" name=\"name\" id=\"name\" value=\"" . $name . "\" size=\"15\" maxlength=\"15\" /> (visible everywhere on PHPDraft)</p>
				<p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Update User\" /></p>
				<p class=\"error\">*Required</p>
			</form>
		</fieldset>";
		}//print_edit definition

		require_once('cleanstring.php');

		require('comm_draft_menu.php');?>
		<div id="content">
		<?php
		if(empty($_POST))		//Entry 1-0
		{//Haven't been given post information
			$user_result = mysql_query("SELECT Username, Name FROM user_login WHERE UserID = '99999' LIMIT 1");
			$user_row = mysql_fetch_array($user_result);
			?>
		<h3>Update User</h3>
		<p>Use this form to update your commissioner login. There is only one login for the entire site, so be sure to pick a strong password.  If you would like to update your current password, you must enter the current password, and then your new password twice (for verification).  Otherwise, leave all three fields blank.</p>
			<?php print_edit($user_row['Username'], $user_row['Name']);
		}elseif(!empty($_POST))			//Entry 3-0
		{//We've been handed something through the form, do some error checking
			require_once('cleanstring.php');

			$username = CleanString(trim($_POST['username']));
			$old_password = CleanString(trim($_POST['old_password']));
			$new_password = CleanString(trim($_POST['new_password']));
			$verify_password = CleanString(trim($_POST['verify_password']));
			$name = CleanString(trim($_POST['name']));


			$user_result = mysql_query("SELECT Password FROM user_login WHERE UserID = '99999' LIMIT 1");
			$user_row = mysql_fetch_array($user_result);

			$pwd_field_count = 0;
			if(!empty($old_password))
			$pwd_field_count++;
			if(!empty($new_password))
			$pwd_field_count++;
			if(!empty($verify_password))
			$pwd_field_count++;

			if(empty($username) || empty($name)) {//If any of these are empty
			?><h3>One or More Fields Empty</h3>
		<p class="error">One or more of the fields were empty.  Enter information for all fields, then hit "Update User" to continue.</p>
			<?php print_edit($username, $name);
			}elseif($pwd_field_count > 0 && $pwd_field_count < 3) {
			?><h3>One or More Password Fields Empty</h3>
		<p class="error">It's not required that you update your password, however you must fill out all three password fields correctly if you wish to update your password!</p>
			<?php print_edit($username, $name);
			}else									//Entry 3-2
			{//We must have been given correct information, process and update the database.
			if($pwd_field_count == 3 && sha1($old_password) != $user_row['Password']) {
				?><h3>Old Password Incorrect</h3>
		<p class="error">The old password that you entered does not match the one currently in the database.  Please try again.</p>
				<?php print_edit($username, $name);
			}elseif($pwd_field_count == 3 && $new_password != $verify_password) {
				?><h3>New Password Does Not Match</h3>
		<p class="error">For verification, your new password must match both times.  Please try again.</p>
				<?php print_edit($username, $name);
			}else {
				$successful = true;
				if(!mysql_query("UPDATE user_login SET Username = '" . $username . "' WHERE UserID = '99999'"))
				$successful = false;
				
				if(!mysql_query("UPDATE user_login SET Name = '".$name."' WHERE UserID = '99999'"))
				$successful = false;

				if($pwd_field_count == 3 && !mysql_query("UPDATE user_login SET Password = '" . sha1($new_password) . "' WHERE UserID = '99999'"))
				$successful = false;

				if($successful) {//Successfully updated the user
				$user_row = mysql_fetch_array(mysql_query("SELECT * FROM user_login WHERE UserID = '99999'"));
				$_SESSION['username'] = $user_row['Username'];
				$_SESSION['password'] = $user_row['Password'];
				?><h3>User Successfully Updated</h3>
		<p class="success">The information for your super user has been successfully updated.</p>
		<p class="success">To go back to your control panel, <a href="control_panel.php?action=home">click here</a>.</p>
				<?php }else {																//Entry 3-2-2
				?><h3>Error: User Not Updated</h3>
		<p class="error">Unfortunately, an error has occurred and the user has not been updated successfully. Please try again below by hitting "Update User".</p>
				<?php print_edit($username, $name);
				}//End of mysql_query branch
			}
			}//end of error-checking branching
		}
		//}?>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>