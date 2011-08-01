<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
	</head>
	<body>
		<div id="page_wrapper">
			<?php DEFINE("ACTIVE_TAB", "DRAFT_CENTRAL");
			require('header.php');?>

			<?php require('/views/shared/main_menu.php');?>
			<div id="content">
				<h3>Please Enter Draft Password</h3>
				<p>This draft is password-protected, which means the commissioner must give you a password to enter below to see the draft.</p>
				<div class="featurebox_center">
					<form method="post" action="draft_login.php">
						<fieldset>
							<legend>Enter your draft password below to continue</legend>
							<input type="hidden" name="did" value="<?php echo $DRAFT_ID;?>" />
							<input type="hidden" name="destination" value="<?php echo $DESTINATION; ?>" />
							<p>
								<label for="draft_password" class="left">Draft Password*:</label>
								<input type="password" name="draft_password" id="draft_password" class="field" tabindex="1" />
							</p>
							<p><input type="submit" name="submit" class="button" value="Login" tabindex="2" /></p>
						</fieldset>
					</form>
				</div>
				<?php if(count($ERRORS) > 0) {?>
					<?php foreach($ERRORS as $error) {?>
						<p class="error">* <?php echo $error;?></p>
					<?php }?>
				<?php } else {?>
					<p class="error">*Required</p>
				<?php }?>
			</div>
			<?php require('footer.php');?>
		</div>
	</body>
</html>