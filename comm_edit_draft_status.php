<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	<script type="text/javascript">
		var current_status = $("#draft_status").val();

		function set_form() {
		var draft_status = $("#draft_status").val();

		if(draft_status == "undrafted") {
			$("#info").show();
		};

		if(draft_status == "in_progress") {
			$("#info").hide();
		};

		};

		$(document).ready(function () {
		$("#info").hide();
		$("#draft_status").change(function() {
			set_form();
		});
		});
	</script>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		require_once('cleanstring.php');

		if(!empty($_REQUEST['draft_id']))
		$draft_id = intval($_REQUEST['draft_id']);
		else
		$draft_id = intval($_REQUEST['did']);

		$draft_status = CleanString(trim($_REQUEST['draft_status']));

		require('comm_draft_menu.php'); ?>

		<div id="content">
		<?php
		function print_edit_status($draft_id, $status) {
			echo "<form action=\"comm_edit_draft_status.php\" method=\"POST\">
			<input type=\"hidden\" name=\"draft_id\" value=\"".$draft_id."\" />
			<fieldset>
			<legend>Change Draft Status</legend>
			<p><label for=\"team_name\">Draft Status*:</label>
			<select name=\"draft_status\" id=\"draft_status\">
				<option value=\"undrafted\"";
			if($status == "undrafted")
			echo " selected";
			echo ">Undrafted</option>
				<option value=\"in_progress\"";
			if($status == "in_progress")
			echo " selected";
			echo ">In Progress</option>
			</select>
			</p>
			<p id=\"info\" class=\"error\">NOTE: If you switch from \"In Progress\" to \"Undrafted\" and have already started to draft, you <em>will</em> lose any and all progress in draft picks.<br />Are you sure?</p>
			<p><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button\" value=\"Change Draft Status\" /></p>
			<p class=\"error\">*Required</p>
			</fieldset>
		</form>";
		}

		$draft_result = mysql_query("SELECT draft_id, draft_status, draft_name FROM draft WHERE draft_id = '".$draft_id."'");
		$draft_num_rows = mysql_num_rows($draft_result);
		$draft_row = mysql_fetch_array($draft_result);
		$current_status = $draft_row['draft_status'];
		$manager_result = mysql_query("SELECT manager_id FROM managers WHERE draft_id = '".$draft_id."'");
		$number_of_managers = mysql_num_rows($manager_result);

		if($current_status == "complete") {
			?><h3>Draft Already Completed</h3>
		<p class="error">You are unable to change the draft's status because it is already complete.</p>
			<?php
		}elseif($number_of_managers == 0) {
			 ?><h3>No Managers Found!</h3>
		<p class="error">Your draft has no managers!  In order to begin the draft, you must first <a href="comm_add_mgrs.php?did=<?php echo $draft_id; ?>">add managers to your draft</a>.  Make sure you double-check all other draft settings before beginning, too!</p>
		<?php
		}elseif(empty($_POST) && ($draft_num_rows == 0)) {
			?><h3>Error: Draft Not Found</h3>
		<p class="error">Unable to continue because the draft were not found.  Please hit the back button and try again.</p>
			<?php
		}elseif(empty($_POST)) {
			?><h3>Change Draft Status</h3>
		<p>To change the draft's status (whether public draft users can access the draft with or without a password), please select below and enter a password (if required):</p>
			<?php print_edit_status($draft_id, $current_status);
		}elseif(!empty($_POST) && (empty($draft_id) || empty($draft_status))) {
			?><h3>One or More Fields Empty</h3>
		<p class="error">One or more of the fields were empty.  Please make sure all fields are completed below and then hit "Change Draft Status" to continue.</p>
			<?php print_edit_status($draft_id, $draft_status);
		}else {
			$sql = "UPDATE draft SET draft_status = '".$draft_status."', ";
			
			if($current_status == "undrafted" && $draft_status == "in_progress") {
				$sql .= "draft_start_time = NOW(), ".
				"draft_current_pick = 1, ".
				"draft_current_round = 1 ";
			}else
				$sql .= "draft_start_time = NULL ";
			
			$sql .= "WHERE draft_id = '".$draft_id."' ";

			$status_successful = mysql_query($sql);

			//Delete any residual pick structure from before
			$sql = "DELETE FROM players WHERE draft_id = '".$draft_id."'";
			$delete_successful = mysql_query($sql);

			$pick_successful = true;

			if($current_status == "undrafted" && $draft_status == "in_progress") {
			$draft_result = mysql_query("SELECT * FROM draft WHERE draft_id = '".$draft_id."' LIMIT 1");
			$draft_row = mysql_fetch_array($draft_result);
			$pick_successful = true;
			$rounds = intval($draft_row['draft_rounds']);

			switch($draft_row['draft_style']) {
				case 'serpentine':
				$even = true;
				$pick = 1;

				for($i=1; $i <= $rounds; $i++) {
					//For each round, change which way we order managers, either front-to-back or back-to-front
					if($even) {
					$manager_result = mysql_query("SELECT manager_id, draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order ASC");
					$even = false;
					}else {
					$manager_result = mysql_query("SELECT manager_id, draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order DESC");
					$even = true;
					}

					while($managers = mysql_fetch_array($manager_result)) {
					$sql = "INSERT INTO players ".
						"(manager_id, player_round, player_pick, draft_id) ".
						"VALUES ".
						"('".$managers['manager_id']."', '".$i."', '".$pick."', '".$draft_id."') ";
					if(!mysql_query($sql))
						$pick_successful = false;

					$pick++;
					}
				}

				break;

				case 'standard':
				$pick = 1;

				for($i=1; $i <= $rounds; $i++) {
					//For each round, find the same order of managers to sift through
					$manager_result = mysql_query("SELECT manager_id, draft_order FROM managers WHERE draft_id = '".$draft_id."' ORDER BY draft_order ASC");

					while($managers = mysql_fetch_array($manager_result)) {
					$sql = "INSERT INTO players ".
						"(manager_id, player_round, player_pick, draft_id) ".
						"VALUES ".
						"('".$managers['manager_id']."', '".$i."', '".$pick."', '".$draft_id."') ";
					if(!mysql_query($sql))
						$pick_successful = false;

					$pick++;
					}
				}
				break;
			}

			}

			if($status_successful && $delete_successful && $pick_successful) {
			?><h3>Draft Status Successfully Updated!</h3>
		<p class="success"><strong><?php echo $draft_row['draft_name']; ?></strong>'s draft status was changed successfully!</p>
		<p class="success">Return to the <a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>">draft's main page</a>.</p><?php
			}else {
			?><h3>Draft Status Not Updated</h3>
			<?php if(!$status_successful) {?><p class="error">PHPDraft encountered an error and could not update the draft status. Please try again.</p><?php }?>
			<?php if(!$delete_successful) {?><p class="error">PHPDraft could not clear previous picks for this draft.</p><?php }?>
			<?php if(!$pick_successful) {?><p class="error">PHPDraft could not setup the pick structure for this draft. Please try again.</p><?php }?>
			<?php }
		}?>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>