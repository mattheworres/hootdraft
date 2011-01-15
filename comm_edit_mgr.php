<?php require('check_login.php');?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
	<script src="js/jquery-1.4.2.min.js" type="text/JavaScript"></script>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php
	    require_once('cleanstring.php');

	    if(!empty($_REQUEST['draft_id']) && !empty($_REQUEST['manager_id'])) {
		$draft_id = intval($_REQUEST['draft_id']);
		$manager_id = intval($_REQUEST['manager_id']);
	    }else {
		$manager_id = intval($_REQUEST['mid']);
		$draft_id = intval($_REQUEST['did']);
	    }

	    $manager_name = CleanString(trim($_REQUEST['manager_name']));
	    $team_name = CleanString(trim($_REQUEST['team_name']));

	    require('comm_draft_menu.php');?>

	    <div id="content">
		<?php
		function print_edit_managers($draft_id, $manager_id, $manager_name = "", $team_name = "") {
		    echo "<form action=\"comm_edit_mgr.php\" method=\"POST\">
			<input type=\"hidden\" name=\"manager_id\" value=\"".$manager_id."\" />
			<input type=\"hidden\" name=\"draft_id\" value=\"".$draft_id."\" />
		    <fieldset>
			<legend>Edit Manager</legend>
			<p><label for=\"manager_name\">Manager Name*:</label>
			<input type=\"text\" name=\"manager_name\" id=\"manager_name\" value=\"".$manager_name."\" /><br />
			<label for=\"team_name\">Manager Team Name*:</label>
			<input type=\"text\" name=\"team_name\" id=\"team_name\" value=\"".$team_name."\" /></p>
			<p><input type=\"submit\" name=\"submit\" class=\"button\" value=\"Edit Manager\" /></p>
			<p class=\"error\">*Required</p>
		    </fieldset>
		</form>";
		}

		include_once('models/draft_model.php');

		$draft_result = mysql_query("SELECT draft_id, draft_status FROM draft WHERE draft_id = '".$draft_id."'");
		$manager_result = mysql_query("SELECT manager_id FROM managers WHERE manager_id = '".$manager_id."'");
		$draft_num_rows = mysql_num_rows($draft_result);
		$manager_num_rows = mysql_num_rows($manager_result);
		$draft_row = mysql_fetch_array($draft_result);

		if(!is_undrafted($draft_id)) {
		    ?><h3>Error: Draft No Longer Editable</h3>
		<p class="error">Unable to continue because the draft has already begun.</p>
		    <?php
		}if
		(empty($_POST) && ($draft_num_rows == 0 || $manager_num_rows == 0)) {
    ?><h3>Error: Draft or Manager Not Found</h3>
		<p class="error">Unable to continue because either the draft or manager were not found.  Please hit the back button and try again.</p>
		    <?php
		}elseif(empty($_POST)) {
		    $manager_result = mysql_query("SELECT * FROM managers WHERE manager_id = '".$manager_id."' AND draft_id = '".$draft_id."'");
		    $manager_row = mysql_fetch_array($manager_result);
    ?><h3>Edit Manager</h3>
		<p>Edit the manager's details below, and then hit "Edit Manager" to continue.</p>
		    <?php print_edit_managers($draft_id, $manager_id, $manager_row['manager_name'], $manager_row['team_name']);
		}elseif(!empty($_POST) && (empty($manager_name) || empty($team_name))) {
    ?><h3>One or More Fields Empty</h3>
		<p class="error">One or more of the fields were empty.  Please make sure all fields are completed below and then hit "Edit Manager" to continue.</p>
		    <?php print_edit_managers($draft_id, $manager_id, $manager_name, $team_name);
		}else {
		    $sql = "UPDATE managers SET ".
			    "manager_name = '".$manager_name."', ".
			    "team_name = '".$team_name."' ".
			    "WHERE draft_id = '".$draft_id."' ".
			    "AND manager_id = '".$manager_id."'";

		    $successful = mysql_query($sql);

		    if($successful) {
	?><h3>Manager Successfully Edited</h3>
		<p class="success"><strong><?php echo $manager_name;?></strong> was edited successfully!</p>
		<p class="success">Return to the <a href="comm_manage_draft.php?did=<?php echo $draft_id;?>">draft's main page</a>.</p><?php
		    }else {
	?><h3>Manager Not Edited</h3>
		<p class="error">An error has occurred and the manager was not edited. Please hit the back button on your browser and try again.</p>
		<p class="error">SQL = <?php echo $sql;?></p><?php
		    }
}?>
	    </div>
<?php require('footer.php'); ?>
	</div>
    </body>
</html>