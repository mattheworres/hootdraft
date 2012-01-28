<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php');?>
		<link href="css/public_draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('includes/header.php');?>

			<?php require('views/shared/public_draft_menu.php');?>
			<div id="content">
				<h3>Draft Home Page - <?php echo $DRAFT->draft_name;?></h3>
				<p>This is the main page for this draft.  Below is some summary information for the draft. Use the links to the right for more functionality.</p>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name;?> - Current Status</legend>
					<div style="width: 70%; float:left;">
						<p><strong>Sport: </strong> <?php echo $DRAFT->draft_sport;?></p>
						<p><strong>Drafting Style: </strong> <?php echo $DRAFT->draft_style;?></p>
						<p><strong># of Rounds: </strong> <?php echo $DRAFT->draft_rounds;?></p>
						<p><strong>Status: </strong> <?php echo $DRAFT->getStatus();?> </p>
						<?php if($DRAFT->isInProgress()) { ?><p><strong>Current Round: </strong><?php echo $DRAFT->draft_current_round; ?></p>
						<p><strong>On the Clock: </strong> Pick #<?php echo $CURRENT_PICK->player_pick; ?> (<?php echo $CURRENT_PICK->manager_name; ?>)</p><?php } ?>
						<?php if($DRAFT->isInProgress() || $DRAFT->isCompleted()) {?><p><strong>Draft Start Time: </strong> <?php echo $DRAFT->draft_start_time;?></p><?php }?>
						<?php if($DRAFT->isCompleted()) {?><p><strong>Draft End Time: </strong> <?php echo $DRAFT->draft_end_time;?></p>
							<p><strong>Time Spent Drafting: </strong> <?php echo $DRAFT->getDraftDuration();?></p><?php }?>
					</div>
					<div style="width: 30%; float:right; text-align: right;">
						<p><img src="images/icons/<?php echo $DRAFT->draft_status;?>.png" alt="<?php echo $DRAFT->draft_status;?>" title="<?php echo $DRAFT->draft_status;?>"/></p>
					</div>
				</fieldset>
				<fieldset>
					<legend>Recent Picks - Last 10</legend>
					<table width="100%">
						<tr>
							<th width="50">Rd #</th>
							<th width="55">Pick #</th>
							<th>Player</th>
							<th>Manager</th>
							<th width="50">Pos.</th>
							<th width="55">Team</th>
						</tr>
						<?php if(count($LAST_TEN_PICKS) == 0) {
							?><td colspan="5"><h2>No picks have been made yet.</h2></td><?php
					} else {
						foreach($LAST_TEN_PICKS as $last_pick) {
								?>
								<tr style="background-color:<?php echo $DRAFT->sports_colors[$last_pick->position]; ?>;">
									<td><?php echo $last_pick->player_round;?></td>
									<td><?php echo $last_pick->player_pick;?></td>
									<td><span class="player-name"><?php echo $last_pick->casualName();?></span></td>
									<td><?php echo $last_pick->manager_name;?></td>
									<td><?php echo $last_pick->position;?></td>
									<td><?php echo $last_pick->team;?></td>
								</tr>
							<?php }
						}?>
					</table>
				</fieldset>
			</div>
<?php require('includes/footer.php');?>
		</div>
	</body>
</html>