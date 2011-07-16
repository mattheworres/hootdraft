<?php
/*
 * view File for Draft Room
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
		$("#first_name").focus();
		});
	</script>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('comm_draft_room_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<p class="success"><?php echo $msg; ?></p>
		<p class="error"><?php echo $err_msg; ?></p>
		<fieldset>
			<legend>Round <?php echo $draft_row['draft_current_round']; ?>, Pick #<?php echo $draft_row['draft_current_pick']; ?></legend>
			<form action="comm_draft_picks.php" method="post">
			<input type="hidden" name="action" value="add_pick" />
			<input type="hidden" name="draft_id" value="<?php echo $draft_id; ?>" />
			<input type="hidden" name="round" value="<?php echo $draft_row['draft_current_round']; ?>" />
			<input type="hidden" name="pick" value="<?php echo $draft_row['draft_current_pick']; ?>" />
			<p><label for="manager_id">Manager*:</label>
				<select name="manager_id" id="manager_id" tabindex="1">
				<?php while($manager_row = mysql_fetch_array($managers_result)) {
					?><option value="<?php echo $manager_row['manager_id']; ?>"<?php if($manager_row['manager_id'] == $current_pick['manager_id']) { echo " selected"; }?>><?php echo $manager_row['manager_name']; ?><?php if($manager_row['manager_id'] == $current_pick['manager_id']) { echo " (current)"; }?></option>
				<?php } ?>
				</select></p>
			<p><label for="first_name">First Name*:</label>
				<input type="text" name="first_name" id="first_name" value="<?php echo $first_name; ?>" autocomplete="off" tabindex="2"/></p>
			<p><label for="last_name">Last Name*:</label>
				<input type="text" name="last_name" id="last_name" value="<?php echo $last_name; ?>" autocomplete="off" tabindex="3"/></p>
			<p><label for="team_abbreviation">Team*:</label>
				<select name="team_abbreviation" tabindex="4">
				<?php foreach($teams as $abbr => $full_name) {
					?><option value="<?php echo $abbr; ?>"<?php if($team_abbreviation == $abbr) { echo " selected";}?>><?php echo $full_name; ?></option>
				<?php } ?>
				</select></p>
			<p><label for="position">Position*:</label>
				<select name="position" tabindex="5">
				<?php foreach($positions as $abbr => $one_position) {
					?><option value="<?php echo $abbr; ?>"<?php if($position == $one_position) { echo " selected";}?>><?php echo $one_position; ?></option>
				<?php } ?>
				</select></p>
			<p><input type="submit" name="submit" class="button" value="Enter Draft Pick"  tabindex="6"/></p>
			<p class="error">*Required</p>
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
	</div>
	</body>
</html>