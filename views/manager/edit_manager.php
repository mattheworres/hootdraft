<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('/includes/meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('/includes/header.php');
		
		require('/views/shared/draft_menu.php'); ?>

		<div id="content">
		<form action="manager.php?action=updateManager" method="POST">
			<input type="hidden" name="mid" value="<?php echo $MANAGER->manager_id; ?>" />
			<input type="hidden" name="did" value="<?php echo $MANAGER->draft_id; ?>" />
			<fieldset>
				<legend>Edit Manager</legend>
				<p><label for="manager_name">Manager Name*:</label>
				<input type="text" name="manager_name" id="manager_name" value="<?php echo $MANAGER->manager_name; ?>" /><br />
				<label for="manager_email">Manager Email:</label>
				<input type="text" name="manager_email" id="manager_email" value="<?php echo $MANAGER->manager_email; ?>" /></p>
				<p><input type="submit" name="submit" class="button" value="Edit Manager" /></p>
				<?php if(count($ERRORS) > 0) { ?>
				<?php foreach($ERRORS as $error) { ?>
				<p class="error">* <?php echo $error; ?></p>
				<?php } ?>
				<?php }else {?>
				<p class="error">*Required</p>
				<?php } ?>
			</fieldset>
		</form>
		</div>
<?php require('/includes/footer.php');; ?>
	</div>
	</body>
</html>