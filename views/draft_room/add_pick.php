<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('includes/meta.php'); ?>
	<link href="css/draft_room.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('includes/header.php'); ?>

		<?php require('views/shared/draft_menu.php'); ?>
		<div id="content">	
		<?php if(isset($SUCCESSES) && count($SUCCESSES) > 0) {?>
			<?php foreach($SUCCESSES as $success) {?>
				<p class="success">* <?php echo $success;?></p>
			<?php }?>
		<?php }?>
		<h3>Enter the Next Draft Pick</h3>
		<fieldset id="add_pick" class="enter_pick">
			<legend>Round <?php echo $DRAFT->draft_current_round; ?>, Pick #<?php echo $DRAFT->draft_current_pick; ?></legend>
			<form action="draft_room.php?action=addPick" method="post">
				<input type="hidden" name="did" value="<?php echo DRAFT_ID; ?>" />
				<input type="hidden" name="pid" value="<?php echo $CURRENT_PICK->player_id; ?>" />
				<input type="hidden" id="league" name="league" value="<?php echo $DRAFT->draft_sport; ?>" />
				<input type="hidden" name="player_round" value="<?php echo $CURRENT_PICK->player_round; ?>" />
				<input type="hidden" name="player_pick" value="<?php echo $CURRENT_PICK->player_pick; ?>" />
				<p>
					<label for="mid">Manager:</label>
					<input type="hidden" value="<?php echo $CURRENT_PICK_MANAGER->manager_id; ?>" name="mid" id="mid" />
					<?php echo $CURRENT_PICK_MANAGER->manager_name; ?>
				</p>
				<p>
					<label for="first_name">First Name*:</label>
					<input type="text" name="first_name" id="first_name" value="<?php echo $CURRENT_PICK->first_name; ?>" tabindex="1"/>
				</p>
				<p>
					<label for="last_name">Last Name*:</label>
					<input type="text" name="last_name" id="last_name" value="<?php echo $CURRENT_PICK->last_name; ?>" tabindex="2"/>
				</p>
				<p>
					<label for="team">Team*:</label>
					<select id="team" name="team" tabindex="3">
					<?php if(strlen($CURRENT_PICK->team) == 0) {?><option selected="selected"></option><?php } ?>
					<?php foreach($DRAFT->sports_teams as $abbr => $sports_team_name) {
						?><option value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->team == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_team_name; ?></option>
					<?php } ?>
					</select>
				</p>
				<p>
					<label for="position">Position*:</label>
					<select id="position" name="position" tabindex="4">
						<?php if(strlen($CURRENT_PICK->position) == 0) {?><option selected="selected"></option><?php } ?>
					<?php foreach($DRAFT->sports_positions as $abbr => $sports_position) {
						?><option style="background-color: <?php echo $DRAFT->sports_colors[$abbr]; ?>" value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->position == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_position; ?></option>
					<?php } ?>
					</select>
				</p>
				<p><input type="submit" name="submit" class="button" value="Enter Draft Pick"  tabindex="5"/></p>
				<?php if(isset($ERRORS) && count($ERRORS) > 0) {?>
					<?php foreach($ERRORS as $error) {?>
						<p class="error">* <?php echo $error;?></p>
					<?php }?>
				<?php } else {?>
					<p class="error">*Required</p>
				<?php }?>
			</form>
		</fieldset>
		<fieldset>
			<legend>Looking Ahead - Next Five Picks</legend>
			<?php $current_round = $CURRENT_PICK->player_round;
			$i = 0;
			if(count($NEXT_FIVE_PICKS) == 0) { ?>
				<p class="success">No more draft picks left! Just enter this last pick, and you're done!</p>
			<?php } else { 
				foreach($NEXT_FIVE_PICKS as $next_pick) { ?>
				<?php if($next_pick->player_round > $current_round) {
					?><p class="round"><strong>Round #<?php echo $next_pick->player_round; ?></strong></p>
				<?php $current_round = $next_pick->player_round; } ?>
				<p><strong><?php echo $kooky_labels[$i]; ?></strong><?php  echo "Pick #" . $next_pick->player_pick . " - " . $next_pick->manager_name; ?></p>
				<?php 
					$i++; 
				}
			}?>
		</fieldset>
		<fieldset>
			<legend>Looking Back - Last Five Picks</legend>
			<?php if(count($LAST_FIVE_PICKS) == 0) { ?>
			<p><strong>No picks have been made yet.</strong></p>
			<?php } else {
				foreach($LAST_FIVE_PICKS as $last_pick) { ?>
			<p style="background-color: <?php echo $DRAFT->sports_colors[$last_pick->position]; ?>;">
				<span class="player-name"><?php echo $last_pick->casualName(); ?></span>
				<?php echo " (Pick #" . $last_pick->player_pick . ", " . $last_pick->team . " - " . $last_pick->position . ")<br/><strong>Manager:</strong> " . $last_pick->manager_name . "<br/>"; ?>
			</p>
			<?php }
			}?>
		</fieldset>
		</div>
		<?php require('includes/footer.php'); ?>
		<script src="js/" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#first_name").focus();
				
				var autocompleteOptions = {
					source: function(request, response) {
						$.ajax({
							type: 'GET',
							url: 'draft.php',	//Where to ask server for data
							dataType: 'json',
							data: { action: 'searchProPlayers',
								did: <?php echo DRAFT_ID; ?>,
								league: $('#league').val(), 
								first: $('#first_name').val(), 
								last: $('#last_name').val(), 
								team: $('#team').val(), 
								position: $('#position').val()
							},
							success: function(data) {
								response($.map(data, function(item) {
									return {
										first_name: item.first_name,
										last_name: item.last_name,
										team: item.team,
										position: item.position,
										label: item.first_name + " " + item.last_name + " (" + item.position + ", " + item.team + ")"
									}
								}));
							}
						});
					},
					minLength: 2,
					select: function(event, ui) {
						var $firstName = $('#first_name'),
							$lastName = $('#last_name'),
							$team = $('#team'),
							$position = $('#position');
						
						
						setTimeout(function() {
							$firstName.val(ui.item.first_name);
							$lastName.val(ui.item.last_name);
							$team.val(ui.item.team);
							$position.val(ui.item.position);
						}, 1);
					}
				};
				
				$('#first_name').autocomplete(autocompleteOptions);
				$('#last_name').autocomplete(autocompleteOptions);
			});
		</script>
	</div>
	</body>
</html>