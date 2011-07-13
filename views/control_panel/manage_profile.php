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
	    require_once('cleanstring.php');

	    require('comm_draft_menu.php');?>
	    <div id="content">
                <h3>Update User</h3>
                <p>Use this form to update your commissioner login. There is only one login for the entire site, so be sure to pick a strong password. If you would like to update your current password, you must enter the current password, and then your new password twice (for verification). Otherwise, leave all three fields blank.</p>
                <fieldset>
                    <legend>Update User</legend>
                    <form action="comm_user_manage.php" method="post">
                        <p><label for="username">Login*:</label>
                        <input type="text" name="username" id="username" value="<?php echo $user_object->user_name;?>" /></p>
                        <p><label for="old_password">Old Password:</label>
                        <input type="password" name="old_password" id="old_password" value="" autocomplete="off" /></p>
                        <p><label for="old_password">New Password:</label>
                        <input type="password" name="new_password" id="new_password" value="" autocomplete="off" /></p>
                        <p><label for="old_password">New Password (verify):</label>
                        <input type="password" name="verify_password" id="verify_password" value="" autocomplete="off" /></p>
                        <p><label for="name">Your Public Name*:</label>
                        <input type="text" name="name" id="name" value="<?php echo $user_object->public_name;?>" size="15" maxlength="15" /> (visible everywhere on PHPDraft)</p>
                        <p><input type="submit" name="submit" class="button" value="Update User" /></p>
                        <?php if(count($ERRORS) > 0) { ?>
                        <?php foreach($ERRORS as $error) { ?>
                        <p class="error">* <?php echo $error;?></p>
                        <?php } ?>
                        <?php }else {?>
                        <p class="error">*Required</p>
                        <?php } ?>
                    </form>
		</fieldset>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>