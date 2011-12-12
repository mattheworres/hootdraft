<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('includes/meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('includes/header.php'); ?>

		<?php require('views/shared/main_menu.php'); ?>
		<div id="content">
		<h3>Please Authenticate</h3>
		<div class="featurebox_center">
				<form method="post" action="login.php?q=1">
				  <fieldset>
				  <legend>Enter your username and password below to continue</legend>
					<p><label for="txt_user" class="left">Username:</label>
					   <input type="text" name="txt_user" id="txt_user" class="field" maxlength="16" value="" tabindex="1" /></p>
					<p><label for="txt_pass" class="left">Password:</label>
					   <input type="password" name="txt_pass" id="txt_pass" class="field" maxlength="16" tabindex="2" /></p>
					<p><input type="submit" name="submit" class="button" value="Login" tabindex="4" /></p>
					</fieldset>
				</form>
			</div>
		<?php if(defined("LOGIN_ERROR")) {
					switch(LOGIN_ERROR) {
						case "DB_NO_MATCH":
							$message = "The username/password combination was incorrect. Please try again.";
							break;

						case "INCORRECT_CREDENTIALS":
							$message = "Your login is incorrect. Please login again.";
							break;
					}?><p><strong>*<?php echo $message; ?></strong></p>
				 <?php } ?>
		</div>
		<?php require('includes/footer.php'); ?>
	</div>
	</body>
</html>