<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link href="css/style.css" type="text/css" rel="stylesheet" />
		<link href="css/jquery-ui-1.10.3.min.css" type="text/css" rel="stylesheet" />
		<title>PHPDraft - <?php echo $DRAFT->draft_name;?> - Draft Board</title>
	</head>
	<body class="draftBoardBody">
		<p style=" width:<?php echo TOTAL_WIDTH;?>px;"><strong><a href="public_draft.php?did=<?php echo DRAFT_ID;?>"><img src="images/icons/back.png" border="0" alt="Go Back" />Go Back</a></strong></p>
		<div id="picks">
			<table id="draft_table" width="<?php echo TOTAL_WIDTH;?>">
				<tr><th class="left_col">Rd.</th><th class="left_col" colspan="<?php echo NUMBER_OF_MANAGERS;?>"><?php echo $DRAFT->draft_name;?> - Draft Board</th></tr>
				<?php
				for($i = 1; $i <= $DRAFT->draft_rounds; ++$i) {
					$picks_row = $ALL_PICKS[$i-1];
					?><tr>
						<td class="left_col" width="10"><?php echo $i;?></td>
						<?php
						foreach($picks_row as $pick) {
							if(isset($pick->pick_time) && strlen($pick->pick_time) > 0) {
								?><td width="<?php echo COL_WIDTH;?>" style="background-color:<?php echo $DRAFT->sports_colors[$pick->position];?>;"># <?php echo $pick->player_pick;?><br />
									<strong><?php echo $pick->first_name . "<br />" . $pick->last_name;?></strong><br />
															(<?php echo $pick->position . " - " . $pick->team;?>)<br />
									<?php echo $pick->manager_name;?></td>
							<?php } else {?>
								<td width="<?php echo COL_WIDTH;?>"># <?php echo $pick->player_pick;?><br />
									&nbsp;<br />
									&nbsp;<br />
									&nbsp;<br />
									<?php echo $pick->manager_name;?></td>
								<?php
							}
						}
						?>
					</tr>
				<?php }?>
			</table>
		</div>
	</body>
	<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.10.3.min.js"></script>
	<script type="text/javascript">
			var poll_time = 1000 * <?php echo BOARD_RELOAD; ?>,
			our_pick = <?php echo $DRAFT->draft_current_pick;?>,
			last_pick = <?php echo $DRAFT->draft_rounds * NUMBER_OF_MANAGERS;?>,
			intervalID;

			if(our_pick < last_pick) {
				intervalID = window.setInterval('check_for_new_picks()', poll_time);
			};

			function check_for_new_picks() {
				var current_pick = parseInt($.ajax({
					url: 'public_draft.php?action=checkPick&did=<?php echo DRAFT_ID;?>',
					async: false,
					success: function(data, textStatus) {
						currentPick = parseInt(data, 10);
					}
				}).responseText, 10);

				if(our_pick < current_pick) {
					our_pick++;
					if(our_pick > last_pick) {
						clearInterval(intervalID);
					};

					$("#picks").load('public_draft.php?action=loadDraftBoard&did=<?php echo DRAFT_ID;?>');
				};
			};
	</script>
</html>