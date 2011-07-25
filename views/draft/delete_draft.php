<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
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
		$draft_id = CleanString(trim($_REQUEST['did']));
		if(empty($draft_id))
		require('comm_menu.php');
		else
		require('comm_draft_menu.php'); ?>
		<div id="content-wide">
		<fieldset>
			<legend>Are You Sure?</legend>
			<p>Are you sure you want to delete this draft?  Once it has been deleted, it is irreversible.  When a draft is deleted, all managers, draft picks, player information, etc. that is associated with that draft is ALSO deleted permanently!</p>
				<p>If you are sure, then please answer this trivial math problem: <strong>33 + 1 + 77 = </strong></p>
			<form action="draft.php?action=confirmDelete" method="post">
				<input type="hidden" name="did" value="<?php echo DRAFT_ID; ?>" />
				<p><label for="txt_answer">Your Answer*:</label>
				<input type="text" name="txt_answer" id="txt_answer" value="<?php echo ANSWER; ?>" autocomplete="off" /></p>
				<p><input type="submit" name="submit" class="button" value="Delete Draft, Im Sure!" /></p>
				<?php if(count($ERRORS) > 0) {?>
					<?php foreach($ERRORS as $error) {?>
						<p class="error">* <?php echo $error;?></p>
					<?php }?>
				<?php } else {?>
					<p class="error">*Required</p>
				<?php }?>
			</form>
		</fieldset>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>