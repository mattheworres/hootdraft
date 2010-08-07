<?php
/*
 * Template File for Draft Room
 *
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<?php require('meta.php'); ?>
	<script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	    $(document).ready(function() {
		$("#refresh").click(function() {
		   $("#stats").load('draft_stats.php?action=reload_stats&draft_id=<?php echo $draft_id;?>');
		});
	    });
	</script>
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('draft_menu.php'); ?>
	    <div id="content">
		<h3><?php echo $title;?></h3>
		<p>Use this page to see summary statistics about the draft.</p>
		<fieldset>
		    <legend><?php echo $draft_row['draft_name'];?> - Statistics Summary</legend>
		    <?php if($draft_row['draft_status'] == "in_progress") {?><p class="success"><a href="javascript:void(0);" id="refresh"><img src="images/icons/refresh.png" class="small_link" alt="Refresh Statistics" />&nbsp;Refresh Statistics</a></p><?php } ?>
		    <div id="stats">
			<p><strong>Longest Average Pick Time (The &quot;Hooooold Oonnn&quot; Award)</strong><br /><?php echo $stats['Average_Time_High']['manager_name'] . " - " . seconds_to_words($stats['Average_Time_High']['pick_average']);?></p>
			<p><strong>Shortest Average Pick Time (The Quickie Award)</strong><br /><?php echo $stats['Average_Time_Low']['manager_name'] . " - " . seconds_to_words($stats['Average_Time_Low']['pick_average']);?></p>
			<p><strong>Longest Single Pick (The Slowpoke Rodriguez Award)</strong><br /><?php echo $stats['High_Time']['manager_name'] . " - " . seconds_to_words($stats['High_Time']['pick_max']);?></p>
			<p><strong>Shortest Single Pick (The Speedy Gonzalez Award)</strong><br /><?php echo $stats['Low_Time']['manager_name'] . " - " . seconds_to_words($stats['Low_Time']['pick_min']);?></p>
			<p><strong>Average Pick Time</strong><br /><?php echo seconds_to_words($stats['Average_Time']['pick_average']);?></p>
			<p><strong>Longest Round Time</strong><br />Round #<?php echo $stats['Round_Time_High']['player_round'] . " - " . seconds_to_words($stats['Round_Time_High']['round_time']);?></p>
			<p><strong>Shortest Round Time</strong><br />Round #<?php echo $stats['Round_Time_Low']['player_round'] . " - " . seconds_to_words($stats['Round_Time_Low']['round_time']);?></p>
			<p><strong>Most Drafted Team</strong><br /><?php echo $teams[$stats['High_Team']['team']] . " - " . $stats['High_Team']['team_occurences'];?> of their players drafted</p>
			<p><strong>Least Drafted Team</strong><br /><?php echo $teams[$stats['Low_Team']['team']] . " - " . $stats['Low_Team']['team_occurences'];?> of their players drafted</p>
			<p><strong>Most Drafted Position</strong><br /><?php echo $positions[$stats['High_Position']['position']] . " - " . $stats['High_Position']['position_occurences'];?> of them drafted</p>
			<p><strong>Least Drafted Position</strong><br /><?php echo $positions[$stats['Low_Position']['position']] . " - " . $stats['Low_Position']['position_occurences'];?> of them drafted</p>
		    </div>
		</fieldset>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>