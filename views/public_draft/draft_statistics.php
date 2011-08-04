<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
		<link href="css/public_draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('/includes/header.php');?>

			<?php require('/views/shared/public_draft_menu.php');?>
			<div id="content">
				<h3>Draft Statistics</h3>
				<p>Below are some statistics (for fun) that are updated during the entire draft. If you want to win the coveted Quickie, make sure you have your mind made up by the time you're on the clock, and let the Commish know immediately!</p>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name;?> - Statistics Summary</legend>
					<?php if($DRAFT->isInProgress()) {?><p class="success"><a href="#" id="refresh"><img src="images/icons/refresh.png" class="small_link" alt="Refresh Statistics" />&nbsp;Refresh Statistics</a></p><?php }?>
					<div id="stats">
						<p class="success">Last refreshed: <?php echo $NOW; ?></p>
						<p><strong>Longest Average Pick Time (The &quot;Hooooold Oonnn&quot; Award)</strong><br /><?php echo $STATS->hold_on_manager_name; ?> - <?php echo $STATS->hold_on_pick_time; ?></p>
						<p><strong>Shortest Average Pick Time (The Quickie Award)</strong><br /><?php echo $STATS->quickie_manager; ?> - <?php echo $STATS->quickie_pick_time; ?></p>
						<p><strong>Longest Single Pick (The Slowpoke Rodriguez Award)</strong><br /><?php echo $STATS->slowpoke_manager_name; ?> - <?php echo $STATS->slowpoke_pick_time; ?></p>
						<p><strong>Shortest Single Pick (The Speedy Gonzalez Award)</strong><br /><?php echo $STATS->speedy_manager_name; ?> - <?php echo $STATS->speedy_pick_time; ?></p>
						<p><strong>Average Pick Time</strong><br /><?php echo $STATS->average_pick_time; ?></p>
						<p><strong>Longest Round Time</strong><br />Round #<?php echo $STATS->longest_round; ?> - <?php echo $STATS->longest_round_time; ?></p>
						<p><strong>Shortest Round Time</strong><br />Round #<?php echo $STATS->shortest_round; ?> - <?php echo $STATS->shortest_round_time; ?></p>
						<p><strong>Most Drafted Team</strong><br /><?php echo $STATS->most_drafted_team; ?> - <?php echo $STATS->most_drafted_team_count; ?> of their players drafted</p>
						<p><strong>Least Drafted Team</strong><br /><?php echo $STATS->least_drafted_team; ?> - <?php echo $STATS->least_drafted_team_count; ?> of their players drafted</p>
						<p><strong>Most Drafted Position</strong><br /><?php echo $STATS->most_drafted_position; ?> - <?php echo $STATS->most_drafted_position_count; ?> of them drafted</p>
						<p><strong>Least Drafted Position</strong><br /><?php echo $STATS->least_drafted_position; ?> - <?php echo $STATS->least_drafted_position_count; ?> of them drafted</p>
					</div>
				</fieldset>
			</div>
			<?php require('/includes/footer.php');;?>
			<script type="text/javascript">
				$(document).ready(function() {
					$("#refresh").click(function() {
						$("#stats").load('public_draft.php?action=loadStats&did=<?php echo DRAFT_ID;?>');
					});
				});
			</script>
		</div>
	</body>
</html>