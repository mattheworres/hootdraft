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
		$draft_id = CleanString(trim($_REQUEST['did']));
		if(empty($draft_id))
		require('comm_menu.php');
		else
		require('comm_draft_menu.php');?>
		<div id="content-wide">
		<?php
		function print_delete($draft_id, $answer = "") {
			echo "<fieldset>
			<legend>Are You Sure?</legend>
			<p>Are you sure you want to delete this draft?  Once it has been deleted, it is irreversible.  When a draft is deleted, all managers, draft picks, player information, etc. that is associated with that draft is ALSO deleted permanently!</p>
				<p>If you are sure, then please input the answer to this mathematical problem: <strong>33 + 1 + 77 = </strong></p>
			<form action=\"comm_delete_draft.php\" method=\"post\">
				<input type=\"hidden\" name=\"txt_draft_id\" value=\"" . $draft_id . "\" />
				<p><label for=\"txt_answer\">Your Answer*:</label>
				<input type=\"text\" name=\"txt_answer\" id=\"txt_answer\" value=\"" . $answer . "\" autocomplete=\"off\" /></p>
				<p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Delete Draft, Im Sure!\" /></p>
				<p class=\"error\">*Required</p>
			</form>
		</fieldset>";
		}//end print_delete()

		if(empty($draft_id) && empty($_POST)) {//We weren't given an ID number
			?>
		<h3>Draft Not Found</h3>
		<p class="error">The draft you have attempted to delete does not exist.</p>
	<?php
}elseif(!empty($draft_id) && empty($_POST)) {//We've been given an ID number, determine if it's valid
			$draft_result = mysql_query("SELECT draft_id FROM draft WHERE draft_id = '" . $draft_id . "'");
			$draft_exists = mysql_num_rows($draft_result);

			if(!$draft_exists) {//Draft doesn't exist
			?><h3>Draft Not Found</h3>
		<p class="error">The draft you have attempted to delete does not exist.</p>
			<?php
			}else {//The draft does exist, we must ask the user if they're sure.
			?><h3>Delete Draft</h3>
	<?php print_delete($draft_id);
			}
		}elseif(!empty($_POST)) {//The user has submitted the form with their answer
			require_once('cleanstring.php');
			$draft_id = CleanString($_REQUEST['txt_draft_id']);
			$answer = CleanString($_REQUEST['txt_answer']);

			if(empty($answer)
				|| empty($draft_id)) {//If either are empty
			?><h3>You Must Answer!</h3>
		<p class="error"> You must input an answer to the problem to verify you truly do want to delete this draft. Please answer the question correctly to continue below.</p>
			<?php print_delete($draft_id, $answer);
			}elseif($answer != 111) {//User didn't answer correctly
			?><h3>Incorrect Answer</h3>
		<p class="error">The answer you have provided is incorrect.  Please carefully read the question and then answer accordingly below.</p>
			<?php print_delete($draft_id, $answer);
			}else {//User answered correctly
	$manager_result = mysql_query("SELECT manager_id FROM managers WHERE draft_id = '" . $draft_id . "'");
			while($manager_row = mysql_fetch_array($manager_result)) {//Go through each manager, delete all players associated with that manager.
				if(!mysql_query("DELETE FROM players WHERE manager_id = '" . $manager_row['manager_id'] . "'")) {?><p class="error">An error occurred while deleting this draft. Please consult <a href="mailto:matthew.orres@gmail.com">Matthew</a> as there may be a loss of data integrity if this error is not properly fixed. #80</p>
				<?php exit(1);
				}
		if(!mysql_query("DELETE FROM managers WHERE manager_id = '" . $manager_row['manager_id'] . "'")) {?><p class="error">An error occurred while deleting this draft. Please consult <a href="mailto:matthew.orres@gmail.com">Matthew</a> as there may be a loss of data integrity if this error is not properly fixed. #83</p>
				<?php exit(1);
				}
			}

			if(!mysql_query("DELETE FROM draft WHERE draft_id = '" . $draft_id . "' LIMIT 1")) {?><p class="error">An error occurred while deleting this draft. Please consult <a href="mailto:matthew.orres@gmail.com">Matthew</a> as there may be a loss of data integrity if this error is not properly fixed. #88</p>
				<?php exit(1);
			}
			?><h3>Draft Removed Correctly!</h3>
		<p class="success">You have successfully deleted the draft and ALL associated managers and players from the database.</p>
			<?php
			}
		}
		?>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>