<?php
/*
 * view File for Draft Room
 *
*/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="css/style.css" type="text/css" rel="stylesheet" />
	<script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>

	<script type="text/javascript">
		var poll_time = 1000 * 8;
		var our_pick = <?php echo $draft_row['draft_current_pick'];?>;
		var last_pick = <?php echo $picks_total;?>;
		var intervalID;

		if(our_pick < last_pick) {
		intervalID = window.setInterval('check_for_new_picks()', poll_time);
		};
		

		function check_for_new_picks() {
		 var current_pick = $.ajax({
		 url: "draft_board.php?action=check_pick&draft_id=<?php echo $draft_row['draft_id'];?>",
		 async: false
		}).responseText;

		if(parseInt(our_pick) < parseInt(current_pick)) {
		  our_pick++;
		  if(our_pick > last_pick) {
			  clearInterval(intervalID);
		  };
		  
		  $("#picks").load('draft_board.php?action=load_board&draft_id=<?php echo $draft_row['draft_id'];?>');
		};
		};
	</script>
	<title>PHPDraft - <?php echo $draft_row['draft_name'];?> - Draft Board</title>
	</head>
	<body style="margin-top: 1px;">
	<p style="margin-left: auto; margin-right: auto; margin-top: 0px; margin-bottom: 1px; width:<?php echo $total_width;?>px; text-align: left; vertical-align: middle;"><strong><a href="draft_main.php?draft_id=<?php echo $draft_row['draft_id'];?>"><img src="images/icons/back.png" border="0" alt="Go Back" />Go Back</a></strong></p>
	<div id="picks">
		<table id="draft_table" width="<?php echo $total_width;?>">
		<tr><th class="left_col">Rd.</th><th class="left_col" colspan="<?php echo $number_of_managers;?>"><?php echo $draft_row['draft_name'];?> - Draft Board</th></tr>
		<?php
		for($i = 1; $i <= $rounds; $i++) {
			?><tr><td class="left_col" width="10"><?php echo $i;?></td>
			<?php
			while($pick_row = mysql_fetch_array($picks_result[$i])) {
				if($pick_row['pick_time'] != '') {
				?><td width="<?php echo $col_width;?>" bgcolor="<?php echo $position_colors[$pick_row['position']];?>"># <?php echo $pick_row['player_pick'];?><br />
			<strong><?php echo $pick_row['first_name'] . "<br />" . $pick_row['last_name'];?></strong><br />
			(<?php echo $pick_row['position'] ." - " . $pick_row['team'];?>)<br />
					<?php echo $pick_row['manager_name'];?></td>
				<?php
				}else { ?>
			<td width="<?php echo $col_width;?>"># <?php echo $pick_row['player_pick'];?><br />
			&nbsp;<br />
			&nbsp;<br />
			&nbsp;<br />
					<?php echo $pick_row['manager_name'];?></td>
				<?php }
			}
			}?>
		</table>
	</div>
	</body>
</html>