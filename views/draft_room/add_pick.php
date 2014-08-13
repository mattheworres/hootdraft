<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('includes/meta.php'); ?>
	<link href="css/draft_room.css" type="text/css" rel="stylesheet" />
  <link href="css/draft_board_dynamic_styles.php" type="text/css" rel="stylesheet" />
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
			<form action="draft_room.php?action=addPick" method="post" id="addPickForm" data-has-autocomplete="<?php echo $PHPD->useAutocomplete() == true; ?>">
				<input type="hidden" name="did" id="draft_id" value="<?php echo DRAFT_ID; ?>" />
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
					<input type="text" name="first_name" id="first_name" value="<?php echo $CURRENT_PICK->first_name; ?>" tabindex="1" required/>
				</p>
				<p>
					<label for="last_name">Last Name*:</label>
					<input type="text" name="last_name" id="last_name" value="<?php echo $CURRENT_PICK->last_name; ?>" tabindex="2" required/>
				</p>
				<p>
					<label for="team">Team*:</label>
					<select id="team" name="team" tabindex="3" required>
					<?php if(strlen($CURRENT_PICK->team) == 0) {?><option selected="selected"></option><?php } ?>
					<?php foreach($DRAFT->sports_teams as $abbr => $sports_team_name) {
						?><option value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->team == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_team_name; ?></option>
					<?php } ?>
					</select>
				</p>
				<p>
					<label for="position">Position*:</label>
					<select id="position" name="position" tabindex="4" required>
						<?php if(strlen($CURRENT_PICK->position) == 0) {?><option selected="selected"></option><?php } ?>
					<?php foreach($DRAFT->sports_positions as $abbr => $sports_position) {
						?><option style="background-color: <?php echo $DRAFT->sports_colors[$abbr]; ?>" value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->position == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_position; ?></option>
					<?php } ?>
					</select>
				</p>
				<p><input type="submit" name="submit" class="button" id="addPickButton" value="Enter Draft Pick"  tabindex="5"/></p>
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
		<script type="text/javascript">

		</script>
	</div>
  <div id="alreadyPickedDialog">
    It looks like <strong><span class="pickName"></span></strong> might have already been picked:<br/><br/>

    <ol class="matchingPicks">

    </ol>
    <br/><br/>

    Are you sure you want to enter this pick?
  </div>
	</body>
  <script src="js/underscore-min.js"></script>
  <script src="js/draft_room/add_pick.js"></script>
  <script type="text/template" id="matchingPickTemplate">
    <li><strong class="<%- player.positionClass %>"><%- player.first_name %> <%- player.last_name %></strong> (<%- player.position %>, <%- player.team %>) - Pick #<%- player.player_pick %> by <%- player.manager_name %></li>
  </script>
</html>