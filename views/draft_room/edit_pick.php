<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('/includes/meta.php'); ?>
	<link href="css/draft_room.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('/includes/header.php'); ?>

		<?php require('/views/shared/draft_room_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<p class="success"><?php echo $msg; ?></p>
		<p class="error"><?php echo $err_msg; ?></p>
		<fieldset class="enter_pick">
			<legend>Edit Pick</legend>
			<form action="draft_room.php?action=editPick" method="post">
			<input type="hidden" name="did" value="<?php echo DRAFT_ID; ?>" />
			<input type="hidden" name="pid" value="<?php echo PLAYER_ID; ?>" />
			<p>Edit the pick details below as needed then hit "Edit Pick":</p>
			<p><label for="manager_id">Manager*:</label>
				<select name="manager_id">
				<?php foreach($MANAGERS as $manager) {
					?><option value="<?php echo $manager->manager_id; ?>"<?php if($manager->manager_id == $EDIT_PLAYER->manager_id) { echo " selected=\"selected\""; }?>><?php echo $manager->manager_name; ?><?php if($manager->manager_id == $EDIT_PLAYER->manager_id) { echo " (current)"; }?></option>
				<?php } ?>
				</select></p>
			<p><label for="first_name">First Name*:</label>
				<input type="text" name="first_name" id="first_name" value="<?php echo $EDIT_PLAYER->first_name; ?>"/></p>
			<p><label for="last_name">Last Name*:</label>
				<input type="text" name="last_name" id="last_name" value="<?php echo $EDIT_PLAYER->last_name; ?>"/></p>
			<p><label for="team">Team*:</label>
				<select name="team">
				<?php foreach($DRAFT->sports_teams as $abbr => $sports_team_name) {
					?><option value="<?php echo $abbr; ?>"<?php if($EDIT_PLAYER->team == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_team_name; ?></option>
				<?php } ?>
				</select></p>
			<p><label for="position">Position*:</label>
				<select name="position">
				<?php foreach($DRAFT->sports_positions as $abbr => $sports_position) {
					?><option style="background-color: <?php echo $DRAFT->sports_colors[$abbr]; ?>" value="<?php echo $abbr; ?>"<?php if($EDIT_PLAYER->position == $abbr) { echo " selected=\"selected\"";}?>><?php echo $sports_position; ?></option>
				<?php } ?>
				</select></p>
			<p><input type="submit" name="submit" class="button" value="Edit Draft Pick" /></p>
			<?php if(count($ERRORS) > 0) {?>
				<?php foreach($ERRORS as $error) {?>
					<p class="error">* <?php echo $error;?></p>
				<?php }?>
			<?php } else {?>
				<p class="error">*Required</p>
			<?php }?>
			</form>
		</fieldset>
		</div>
		<?php require('/includes/footer.php');; ?>
	</div>
	</body>
</html>