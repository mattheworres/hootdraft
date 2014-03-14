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
				<?php if($DRAFT->isUndrafted()) { ?>
				<p class="success">This draft has not yet started. Once the commissioner starts the draft, this page will auto-update.</p>
				<?php } ?>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name;?> - Current Status</legend>
					<div class="draftInfo">
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
					<div class="draftStatusImage">
						<p><img src="images/icons/<?php echo $DRAFT->draft_status;?>.png" alt="<?php echo $DRAFT->draft_status;?>" title="<?php echo $DRAFT->draft_status;?>"/></p>
					</div>
				</fieldset>
				<?php if(!$DRAFT->isUndrafted()) { ?>
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
				<?php } ?>
			</div>
			<?php require('includes/footer.php');?>
			<?php if($DRAFT->isUndrafted()) { ?>
			<script type="text/javascript">
				$(document).ready(function() {
					var interval = setInterval(function() {
						$.ajax({
							type: 'GET',
							dataType: 'json',
							url: 'public_draft.php',
							data: { did: <?PHP echo DRAFT_ID; ?>, action: 'isDraftReady' },
							success: function(data) {
								if(data.IsDraftReady == true) {
									clearInterval(interval);
									location.reload();
									$('#informationDialog').html('The draft is now ready, if the page does not automatically refresh itself go ahead and reload this page.')
															.dialog('open');
								}
							}
						});
					}, 5000);
					
					$.each($('#right_side a'), function() {
						$(this).replaceWith($('<span>' + this.innerHTML + '</span>'));
					});
				});
			</script>
			<?php } ?>
		</div>
	</body>
</html>