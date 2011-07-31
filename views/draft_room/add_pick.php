<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	<link href="css/draft_room.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('/views/shared/draft_room_menu.php'); ?>
		<div id="content">	
		<?php if(count($SUCCESSES) > 0) {?>
			<?php foreach($SUCCESSES as $success) {?>
				<p class="success">* <?php echo $success;?></p>
			<?php }?>
		<?php }?>
		<h3>Enter the Next Draft Pick</h3>
		<fieldset>
			<legend>Round <?php echo $DRAFT->current_round; ?>, Pick #<?php echo $DRAFT->current_pick; ?></legend>
			<form action="draft_room.php?action=addPick" method="post">
			<input type="hidden" name="did" value="<?php echo DRAFT_ID; ?>" />
			<input type="hidden" name="pid" value="<?php echo $CURRENT_PICK->player_id; ?>" />
			<input type="hidden" name="player_round" value="<?php echo $CURRENT_PICK->player_round; ?>" />
			<input type="hidden" name="player_pick" value="<?php echo $CURRENT_PICK->player_pick; ?>" />
			<p>
				<label for="mid">Manager*:</label>
				<select name="mid" id="mid" tabindex="1">
				<?php foreach($MANAGERS as $manager) {?>
					<option value="<?php echo $manager->manager_id; ?>"<?php if($manager->manager_id == $CURRENT_PICK->manager_id) { echo " selected=\"selected\""; }?>><?php echo $manager->manager_name; ?><?php if($manager->manager_id == $CURRENT_PICK->manager_id) { echo " (on the clock)"; }?></option>
				<?php } ?>
				</select>
			</p>
			<p>
				<label for="first_name">First Name*:</label>
				<input type="text" name="first_name" id="first_name" value="<?php echo $CURRENT_PICK->first_name; ?>" tabindex="2"/>
			</p>
			<p>
				<label for="last_name">Last Name*:</label>
				<input type="text" name="last_name" id="last_name" value="<?php echo $CURRENT_PICK->last_name; ?>" tabindex="3"/>
			</p>
			<p>
				<label for="team">Team*:</label>
				<select name="team" tabindex="4">
				<?php if(strlen($CURRENT_PICK->team) == 0) {?><option selected="selected"></option><?php } ?>
				<?php foreach($DRAFT->sports_teams as $abbr => $sports_team_name) {
					?><option value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->team == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_team_name; ?></option>
				<?php } ?>
				</select>
			</p>
			<p>
				<label for="position">Position*:</label>
				<select name="position" tabindex="5">
					<?php if(strlen($CURRENT_PICK->position) == 0) {?><option selected="selected"></option><?php } ?>
				<?php foreach($DRAFT->sports_positions as $abbr => $sports_position) {
					?><option value="<?php echo $abbr; ?>"<?php if($CURRENT_PICK->position == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_position; ?></option>
				<?php } ?>
				</select>
			</p>
			<p><input type="submit" name="submit" class="button" value="Enter Draft Pick"  tabindex="6"/></p>
			<?php if(count($ERRORS) > 0) {?>
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
			foreach($NEXT_FIVE_PICKS as $next_pick) { ?>
			<?php if($next_pick->player_round > $current_round) {
				?><p class="round"><strong>Round #<?php echo $next_pick->player_round; ?></strong></p>
			<?php $current_round = $next_pick->player_round; } ?>
			<p><strong><?php echo $kooky_labels[$i]; ?></strong><?php  echo "Pick #" . $next_pick->player_pick . " - " . $next_pick->manager_name; ?></p>
			<?php 
				$i++;
			} ?>
		</fieldset>
		<fieldset>
			<legend>Looking Back - Last Five Picks</legend>
			<?php foreach($LAST_FIVE_PICKS as $last_pick) { ?>
			<p style="background-color: <?php echo $DRAFT->sports_colors[$last_pick->position]; ?>;"><strong><?php  echo "Pick #" . $last_pick->player_pick . " - "; ?></strong> <?php echo $last_pick->casualName() . " (" . $last_pick->team . " - " . $last_pick->position . ")<br><strong>Manager:</strong> " . $last_pick->manager_name . "<br><br>"; ?></p>
			<?php } ?>
		</fieldset>
		</div>
		<?php require('footer.php'); ?>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#first_name").focus();
			});
		</script>
	</div>
	</body>
</html>