<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	<script type="text/javascript">
		function set_form() {
		var draft_visibility = $("#draft_visibility").val();
		if (draft_visibility == 0) {
			$("#draft_password").attr('disabled', true);
			$("#draft_password").val('(Disabled)');
		}else {
			$("#draft_password").attr('disabled', false);
			$("#draft_password").val('');
		};
		};
		
		$(document).ready(function () {
		$("#draft_visibility").change(function() {
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
		$draft_id = (int)$_REQUEST['draft_id'];
		else
		$draft_id = (int)$_REQUEST['did'];


		$draft_password = CleanString(trim($_REQUEST['draft_password']));
		$draft_visibility = CleanString(trim($_REQUEST['draft_visibility']));

		require('comm_draft_menu.php'); ?>

		<div id="content">
		<?php
		function print_edit_password($draft_id, $visibility, $password) {
			echo "<form action=\"comm_edit_draft_pass.php\" method=\"POST\">
			<input type=\"hidden\" name=\"draft_id\" value=\"".$draft_id."\" />
			<fieldset>
			<legend>Change Draft Password</legend>
			<p><label for=\"team_name\">Draft Visibility*:</label>
			<select name=\"draft_visibility\" id=\"draft_visibility\">
				<option value=\"1\"";
			if($visibility)
			echo " selected";
			echo ">Private (passworded)</option>
				<option value=\"0\"";
			if(!$visibility)
			echo " selected";
			echo ">Public</option>
			</select>
			</p>
			<p><label for=\"draft_password\">Draft Password*:</label>
			<input type=\"text\" name=\"draft_password\" id=\"draft_password\" value=\"";
			if($visibility)
			echo $password;
			else
			echo "(Disabled)";
			
			echo "\" ";

			if(!$visibility)
			echo " disabled";
			echo "/><br />
			</p>
			<p><input type=\"submit\" name=\"submit\" id=\"submit\" class=\"button\" value=\"Change Draft Visibility\" /></p>
			<p class=\"error\">*Required</p>
			</fieldset>
		</form>";
		}

		$draft_result = mysql_query("SELECT draft_id, draft_password, draft_name FROM draft WHERE draft_id = '".$draft_id."'");
		$draft_num_rows = mysql_num_rows($draft_result);
		$draft_row = mysql_fetch_array($draft_result);
		$current_visibility = (empty($draft_row['draft_password']) ? false : true);

		if(empty($_POST) && ($draft_num_rows == 0)) {
			?><h3>Error: Draft Not Found</h3>
		<p class="error">Unable to continue because the draft were not found.  Please hit the back button and try again.</p>
			<?php
		}elseif(empty($_POST)) {
			?><h3>Change Draft Visibility</h3>
		<p>To change the draft's visibility (whether public draft users can access the draft with or without a password), please select below and enter a password (if required):</p>
			<?php print_edit_password($draft_id, $current_visibility, $draft_row['draft_password']);
		}elseif(!empty($_POST) && (empty($draft_id) || 
			($draft_visibility == 1 && empty($draft_password))
			)) {
			?><h3>One or More Fields Empty</h3>
		<p class="error">One or more of the fields were empty.  Please make sure all fields are completed below and then hit "Change Draft Visibility" to continue.</p>
			<?php print_edit_password($draft_id, $draft_visibility, $draft_password);
		}else {
			if($draft_visibility == 1) {
			$sql = "UPDATE draft SET ".
				"draft_password = '".$draft_password."' ".
				"WHERE draft_id = '".$draft_id."' ";
			}else {
			$sql = "UPDATE draft SET ".
				"draft_password = NULL ".
				"WHERE draft_id = '".$draft_id."' ";
			}

			$successful = mysql_query($sql);

			if($successful) {
			?><h3>Draft Visibility Successfully Updated!</h3>
		<p class="success"><strong><?php echo $draft_row['draft_name']; ?></strong>'s draft visibility was changed successfully!</p>
		<p class="success">Return to the <a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>">draft's main page</a>.</p><?php
			}else {
			?><h3>Draft Visibility Not Updated</h3>
		<p class="error">An error has occurred and the draft was not updated. Please hit the back button on your browser and try again.</p><?php
			}
		}?>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>