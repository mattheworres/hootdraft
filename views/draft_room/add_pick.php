<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('comm_draft_room_menu.php'); ?>
		<div id="content">
		<h3>Enter the Next Draft Pick</h3>
		<fieldset>
			<legend>Round <?php echo $DRAFT->current_round; ?>, Pick #<?php echo $DRAFT->current_pick; ?></legend>
			<form action="draft_room.php?action=addPick" method="post">
			<input type="hidden" name="draft_id" value="<?php echo DRAFT_ID; ?>" />
			<input type="hidden" name="player_id" value="<?php echo $CURRENT_PICK->player_id; ?>" />
			<p>
				<label for="manager_id">Manager*:</label>
				<select name="manager_id" id="manager_id" tabindex="1">
				<?php foreach($MANAGERS as $manager) {?>
					<option value="<?php echo $manager->manager_id; ?>"<?php if($manager->manager_id == $CURRENT_PICK->manager_id) { echo " selected"; }?>><?php echo $manager->manager_name; ?><?php if($manager->manager_id == $CURRENT_PICK->manager_id) { echo " (on the clock)"; }?></option>
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
				<label for="team_abbreviation">Team*:</label>
				<select name="team_abbreviation" tabindex="4">
				<?php foreach($teams as $abbr => $full_name) {
					?><option value="<?php echo $abbr; ?>"<?php if($team_abbreviation == $abbr) { echo " selected";}?>><?php echo $full_name; ?></option>
				<?php } ?>
				</select>
			</p>
			<p>
				<label for="position">Position*:</label>
				<select name="position" tabindex="5">
				<?php foreach($positions as $abbr => $one_position) {
					?><option value="<?php echo $abbr; ?>"<?php if($position == $one_position) { echo " selected";}?>><?php echo $one_position; ?></option>
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
			<legend>Looking Ahead - Next Four Picks</legend>
			<p><strong>On Deck:</strong> <?php  echo "Rd #".$on_deck['player_round'].", Pick #".$on_deck['player_pick']." - ".$on_deck['manager_name']; ?></p>
			<p><strong>In the Hole:</strong> <?php  echo "Rd #".$in_the_hole['player_round'].", Pick #".$in_the_hole['player_pick']." - ".$in_the_hole['manager_name']; ?></p>
			<p><strong>Still on the Bench:</strong> <?php  echo "Rd #".$on_the_bench['player_round'].", Pick #".$on_the_bench['player_pick']." - ".$on_the_bench['manager_name']; ?></p>
			<p><strong>Grabbing a Gatorade:</strong> <?php  echo "Rd #".$grabbing_gatorade['player_round'].", Pick #".$grabbing_gatorade['player_pick']." - ".$grabbing_gatorade['manager_name']; ?></p>
		</fieldset>
		<fieldset>
			<legend>Looking Back - Last Five Picks</legend>
			<?php while($last_row = mysql_fetch_array($last_picks)) { ?><p><strong><?php  echo "Pick #".$last_row['player_pick']." - "; ?></strong> <?php echo $last_row['first_name'] . " " . $last_row['last_name'] . " (" . $last_row['team'] . "-" . $last_row['position'] . ")<br><strong>Manager:</strong> " . $last_row['manager_name'] . "<br><br>"; ?></p>
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