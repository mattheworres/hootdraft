<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		function print_edit($draft_id, $draft_name, $draft_sport, $draft_style, $draft_rounds) {
		echo "<fieldset>
			<legend>Edit Draft</legend>
			<form action=\"comm_edit_draft.php\" method=\"post\">
				<input type=\"hidden\" name=\"draft_id\" value=\"" . $draft_id . "\" />
				<p><label for=\"draft_name\">Draft Name*:</label>
				<input type=\"text\" name=\"draft_name\" id=\"draft_name\" value=\"" . $draft_name . "\" /></p>
				<p><label for=\"draft_sport\">Draft Sport*:</label>
				<select name=\"draft_sport\">
					<option value=\"football\"" . ($draft_sport == "football" ? " selected" : "") . ">Football</option>
					<option value=\"baseball\"" . ($draft_sport == "baseball" ? " selected" : "") . ">Baseball</option>
					<option value=\"hockey\"" . ($draft_sport == "hockey" ? " selected" : "") . ">Hockey</option>
				</select></p>
				<p><label for=\"draft_style\">Draft Style*:</label>
				<select name=\"draft_style\">
					<option value=\"serpentine\"".($draft_style == "serpentine" ? " selected" : "") . ">Serpentine Draft</option>
					<option value=\"standard\"".($draft_style == "standard" ? " selected" : "") . ">Standard Draft</option>
				</select></p>
				<p><label for=\"draft_name\"># of Rounds*:</label>
				<input type=\"text\" name=\"draft_rounds\" id=\"draft_rounds\" size=\"2\" maxlength=\"2\" value=\"" . $draft_rounds . "\" /> (players per team)</p>
				<p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Update Draft\" /></p>
				<p class=\"error\">*Required</p>
			</form>
		</fieldset>";
		}//print_edit definition

		require_once('cleanstring.php');
		if(empty($_POST))
		$draft_id = intval($_REQUEST['did']);
		else
		$draft_id = intval($_REQUEST['draft_id']);

		if(empty($draft_id))
		require('comm_menu.php');
		else
		require('comm_draft_menu.php'); ?>
		<div id="content">
		<?php
		if(empty($draft_id) && empty($_POST))		//Entry 1-0
		{//Haven't been given a draft ID OR post information
			?>
		<h3>Draft Not Found!</h3>
		<p class="error">The draft you've attempted to edit does not exist!  Hit your browser's back button and try again. #1</p>
			<?php
		}elseif(!empty($draft_id) && empty($_POST))	//Entry 2-0
		{//We've been given a draft ID, let's see if it exists
			$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '" . $draft_id . "'");
			if(!$draft_row = mysql_fetch_array($draft_result))	//Entry 2-1
			{//If we didn't find said draft in the database, let user know
			?><h3>Draft Not Found!</h3>
		<p class="error">The draft you've attempted to edit does not exist!  Hit your browser's back button and try again. #2</p>
			<?php
			}else							//Entry 2-2
			{//Found said draft in database, output form with draft information in it.
			?><h3>Edit Draft</h3>
		<p>Make all necessary changes below and then hit "Update Draft" to continue.</p>
			<?php print_edit($draft_row['draft_id'], $draft_row['draft_name'], $draft_row['draft_sport'], $draft_row['draft_style'], $draft_row['draft_rounds']);
			}
		}elseif(!empty($_POST))			//Entry 3-0
		{//We've been handed something through the form, do some error checking
			require_once('cleanstring.php');

			$draft_id = CleanString($_POST['draft_id']);
			$draft_name = CleanString($_POST['draft_name']);
			$draft_sport = CleanString($_POST['draft_sport']);
			$draft_style = CleanString($_POST['draft_style']);
			$draft_rounds = intval($_POST['draft_rounds']);

			include_once('models/draft_model.php');

			$draft_result = mysql_query("SELECT draft_id, draft_status FROM draft WHERE draft_name = '" . $draft_name . "' AND draft_sport = '" . $draft_sport . "' AND draft_id != '" . $draft_id . "'");
			$draft_row = mysql_fetch_array($draft_result);

			if(!is_undrafted($draft_id)) {
			?><h3>Draft Already In Progress/Complete</h3>
		<p class="error">The draft you're attempting to edit is no longer editable.</p>
			<?php
			}elseif(empty($draft_id) ||			//Entry 3-1
				empty($draft_name) ||
				empty($draft_sport) ||
				$draft_rounds < 1) {//If any of these are empty
			?><h3>One or More Fields Empty</h3>
		<p class="error">One or more of the fields were empty.  Enter information for all fields, then hit "Update Draft" to continue.</p>
			<?php print_edit($draft_id, $draft_name, $draft_sport, $draft_style, $draft_rounds);
			}elseif($draft_row = mysql_num_rows($draft_result))		//Entry 3-2
			{//If there is another draft with the same name that isn't THIS draft, we can't continue
			?><h3>Draft Name Taken</h3>
		<p class="error">There was another pre-existing draft that already has the name "<?php echo $draft_name; ?>".  Please choose another name and hit "Update Draft" to continue.</p>
			<?php print_edit($draft_id, $draft_name, $draft_sport, $draft_style, $draft_rounds);
			}else									//Entry 3-2
			{//We must have been given correct information, process and update the database.
			if(mysql_query("UPDATE draft SET draft_name = '" . $draft_name . "' WHERE draft_id = '" . $draft_id . "'")		//Entry 3-2-1
				&& mysql_query("UPDATE draft SET draft_sport = '" . $draft_sport . "' WHERE draft_id = '" . $draft_id . "'") &&
				mysql_query("UPDATE draft SET draft_style = '".$draft_style."' WHERE draft_id = '".$draft_id."'") &&
				mysql_query("UPDATE draft SET draft_rounds = ".$draft_rounds." WHERE draft_id = '".$draft_id."'")) {//Successfully updated the draft
				?><h3>Draft Successfully Updated</h3>
		<p class="success">The information for this draft has been successfully updated.</p>
		<p class="success">To continue managing this draft, <a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>">click here</a>.</p>
				<?php }else {																//Entry 3-2-2
				?><h3>Error: Draft Not Updated</h3>
		<p class="error">Unfortunately, an error has occurred and the draft has not been updated successfully. Please try again below by hitting "Update Draft".</p>
				<?php print_edit($draft_id, $draft_name, $draft_sport, $draft_style, $draft_rounds);
			}//End of mysql_query branch
			}//end of error-checking branching
		}
		//}?>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>