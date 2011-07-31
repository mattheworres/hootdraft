<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('/views/shared/public_draft_menu.php'); ?>
		<div id="content">
		<h3>Team Draft Picks - <?php echo $DRAFT->draft_name; ?></h3>
		<p>Select a team from the box below to see their current draft picks.</p>
		<p><label for="mid">Select Manager:</label>
			<select name="mid" id="mid">
			<?php
			foreach($MANAGERS as $manager) {
				?><option value="<?php echo $manager->manager_id; ?>"><?php echo $manager->manager_name; ?></option>
				<?php } ?>
			</select>
			<?php if(!$DRAFT->isCompleted()) {?>&nbsp;<a href="#" id="refresh"><img src="images/icons/refresh.png" class="small_link" alt="Refresh Draft Picks" />&nbsp;(Refresh)</a><?php } ?></p>
		<div id="team">
			<p class="success"><strong>Last refreshed:</strong>  <?php echo $NOW; ?></p>
			<p><strong>Manager: </strong><?php echo $MANAGER->manager_name; ?></p>
			<table width="100%">
				<tr>
					<th width="40">Rd</th>
					<th width="40">Pick</th>
					<th>Player</th>
					<th width="120">Position</th>
					<th width="160">Team</th>
				</tr>
				<?php foreach($MANAGER_PICKS as $pick) { ?>
				<tr style="background-color: <?php echo $DRAFT->sports_colors[$pick->position]; ?>">
					<td><?php echo $pick->player_round; ?></td>
					<td><?php echo $pick->player_pick; ?></td>
					<td><span class="player-name"><?php echo $pick->casualName(); ?></span></td>
					<td><?php echo $pick->position; ?></td>
					<td><?php echo $pick->team; ?></td>
				</tr>
				<?php } ?>
			</table>
		</div>
		</div>
		<?php require('footer.php'); ?>
		<script type="text/javascript">
			var manager_id;

			$(document).ready(function() {
				/*$("#manager_id").change(function() {
					manager_id = $(this).val();
					$("#team").load('public_draft.php?action=loadManagerPicks&did=<?php echo $draft_id; ?>&mid='+manager_id);
				});*/
				
				$("#refresh").live('click', reloadPicks);
				$("#mid").live('change', reloadPicks);
			});
			
			function reloadPicks() {
				var $loadingDialog = $('#loadingDialog');
				$loadingDialog.dialog('open');
				manager_id = $("#mid").val();
				$("#team").load('public_draft.php?action=loadManagerPicks&did=<?php echo DRAFT_ID; ?>&mid='+manager_id, function() { $loadingDialog.dialog('close'); });
			}
		</script>
	</div>
	</body>
</html>