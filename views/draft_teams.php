<?php
/*
 * view File for Draft Room
 *
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	<script type="text/javascript">
		var manager_id;
		$("#team").hide();

		$(document).ready(function() {
		$("#manager_id").change(function() {
			manager_id = $(this).val();
			$("#team").load('draft_teams.php?action=load_team&draft_id=<?php echo $draft_id; ?>&manager_id='+manager_id);
		});
		$("#refresh").click(function() {
			manager_id = $("#manager_id").val();
			$("#team").load('draft_teams.php?action=load_team&draft_id=<?php echo $draft_id; ?>&manager_id='+manager_id);
		})
		});
	</script>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('draft_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<p>Select a team from the box below to see their current draft picks.</p>
		<p><label for="manager_id">Select Manager:</label>
			<select name="manager_id" id="manager_id">
			<option>(select a manager)</option>
			<?php while($all_managers = mysql_fetch_array($all_managers_result)) {
				?><option value="<?php echo $all_managers['manager_id']; ?>"><?php echo $all_managers['manager_name'] . " - " . $all_managers['team_name']; ?></option>
				<?php } ?>
			</select>
			<?php if($draft_row['draft_status'] != "complete") {?>&nbsp;<a href="javascript:void();" id="refresh"><img src="images/icons/refresh.png" class="small_link" alt="Refresh Draft Picks" />&nbsp;(Refresh)</a><?php } ?></p>
		<div id="team"></div>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>