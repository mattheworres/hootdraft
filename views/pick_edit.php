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
    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('comm_draft_room_menu.php'); ?>
	    <div id="content">
		<h3><?php echo $title;?></h3>
		<p class="success"><?php echo $msg;?></p>
		<p class="error"><?php echo $err_msg;?></p>
		<fieldset>
		    <legend>Edit Pick</legend>
		    <form action="comm_draft_picks.php" method="post">
			<input type="hidden" name="action" value="edit_pick" />
			<input type="hidden" name="draft_id" value="<?php echo $draft_id;?>" />
			<input type="hidden" name="pick_id" value="<?php echo $pick_id;?>" />
			<p>Edit the pick details below as needed then hit "Edit Pick":</p>
			<p><label for="manager_id">Manager*:</label>
			    <select name="manager_id">
				<?php while($manager_row = mysql_fetch_array($managers_result)) {
				    ?><option value="<?php echo $manager_row['manager_id'];?>"<?php if($manager_row['manager_id'] == $pick_row['manager_id']) { echo " selected"; }?>><?php echo $manager_row['manager_name'];?><?php if($manager_row['manager_id'] == $pick_row['manager_id']) { echo " (current)"; }?></option>
				<?php } ?>
			    </select></p>
			<p><label for="first_name">First Name*:</label>
			    <input type="text" name="first_name" id="first_name" value="<?php echo $pick_row['first_name'];?>" autocomplete="off"/></p>
			<p><label for="last_name">Last Name*:</label>
			    <input type="text" name="last_name" id="last_name" value="<?php echo $pick_row['last_name'];?>" autocomplete="off"/></p>
			<p><label for="team_abbreviation">Team*:</label>
			    <select name="team_abbreviation">
				<?php foreach($teams as $abbr => $full_name) {
				    ?><option value="<?php echo $abbr;?>"<?php if($pick_row['team'] == $abbr) { echo " selected";}?>><?php echo $full_name;?></option>
				<?php } ?>
			    </select></p>
			<p><label for="position">Position*:</label>
			    <select name="position">
				<?php foreach($positions as $abbr => $one_position) {
				    ?><option value="<?php echo $abbr;?>"<?php if($pick_row['position'] == $one_position) { echo " selected";}?>><?php echo $one_position;?></option>
				<?php } ?>
			    </select></p>
			<p><input type="submit" name="submit" class="button" value="Edit Draft Pick" /></p>
			<p class="error">*Required</p>
		    </form>
		</fieldset>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>