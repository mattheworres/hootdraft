<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
		<link href="css/draft_room.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('header.php');?>

			<?php require('/views/shared/draft_room_menu.php');?>
			<div id="content">
				<h3>Edit a Draft Pick</h3>
				<p class="success"><?php echo $msg;?></p>
				<p class="error"><?php echo $err_msg;?></p>
				<fieldset>
					<legend>Select a Pick to Edit</legend>
					<form action="comm_draft_picks.php" method="post">
						<input type="hidden" name="action" value="edit" />
						<input type="hidden" name="draft_id" value="<?php echo $draft_id;?>" />
						<p>To edit a pick, select the round you wish to edit first, and any editable picks will show up below. Only picks that have already been made can be edited, so for depending on which round you choose, you may or may not get any rounds listed.</p>
						<div id="selection">
							<p><label for="round">Round*:</label>
								<select name="round" id="round">
									<?php for($i = 1; $i <= $DRAFT->draft_rounds; ++$i) {
										?><option value="<?php echo $i;?>"<?php if($i == 1) { ?> selected="selected"<?php } ?>>Round <?php echo $i;?></option>
									<?php }?>
								</select></p>
							<div id="picks">
								<?php foreach($ROUND_1_PICKS as $editable_pick) { ?>
								<p><a href="draft_room.php?action=editScreen&did=<?php echo DRAFT_ID;?>">Pick # <?php echo $editable_pick->player_pick; ?>, <span class="player-name"><?php echo $editable_pick->casualName(); ?></span> (<?php echo $editable_pick->position . ", " . $editable_pick->team; ?>) - <?php echo $editable_pick->manager_name; ?></a></p>
								<?php } ?>
							</div>
						</div>
					</form>
				</fieldset>
			</div>
			<?php require('footer.php');?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#round').live('change', function() {
						var round_number = parseInt($('#round').val(), 10),
							draft_id = parseInt(<?php echo DRAFT_ID; ?>, 10),
							$pick_div = $('#picks'),
							$loadingDialog = $('#loadingDialog');
							
						$loadingDialog.dialog('open');
						
						$.ajax({
							type: 'POST',
							data: { round: round_number },
							url: 'draft_room.php?action=getEditablePicks&did=' + draft_id,
							success: function(data) {
								$loadingDialog.dialog('close');
								if(data == 'ERROR') {
									alert('There was an error trying to load the editable picks, please try again.');
									return;
								}
								
								var editablePicks = $.parseJSON(data);
								if(editablePicks == null)
									$pick_div.html('<p class="error">There are no editable picks for Round ' + round_number + '</p>');
								else {
									var picks_html = '';
									
									$.each(editablePicks, function() {
										picks_html += '<p><a href="draft_room.php?action=editScreen&did=' + draft_id + '&pid=' + this.player_id + '">Pick # ' + this.player_pick + ', <span class="player-name">' + this.first_name + ' ' + this.last_name + '</span> (' + this.position + ", " + this.team + ') - ' + this.manager_name + '</a></p>\n';
									});
									
									if(picks_html.length == 0)
										$pick_div.empty().html('<p class="error">There are no editable picks for Round ' + round_number + '</p>');
									else
										$pick_div.empty().html(picks_html);
								}
							},
							error: function() {
								$loadingDialog.dialog('close');
								alert('There was an error trying to load the editable picks, please try again.');
							}
						})
					});
				});
				
				
			</script>
		</div>
	</body>
</html>