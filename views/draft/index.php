<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php'); ?>
		<link href="css/draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php 
			require('includes/header.php');
			require('views/shared/draft_menu.php'); ?>
			<div id="content">
				<h3>Manage <?php echo $DRAFT->draft_name; ?> (<?php echo $DRAFT->draft_sport; ?>)</h3>
				<p>Select your option below to begin managing this draft.</p>
				<?php if($DRAFT->isInProgress()) { ?><p class="success">Your draft is in progress! Click "Enter the Draft Room" to the right to get started or continue, commish!</p><?php } ?>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name; ?> - Current Status</legend>
					<div style="width: 70%; float:left;">
						<input type="hidden" id="draft_id" value="<?php echo DRAFT_ID; ?>"/>
						<p><strong>Sport: </strong> <?php echo $DRAFT->draft_sport; ?></p>
						<p><strong>Drafting Style: </strong> <?php echo $DRAFT->draft_style; ?></p>
						<p><strong># of Rounds: </strong> <?php echo $DRAFT->draft_rounds; ?></p>
						<p><strong>Status: </strong> <?php echo $DRAFT->draft_status; ?> </p>
						<?php if($DRAFT->isCompleted()) { ?><p><strong>Total Draft Duration: </strong><?php echo $DRAFT->getDraftDuration(); ?></p><?php } ?>
						<p><strong>Draft Visibility: </strong> <span id="draft_visibility"><?php echo $DRAFT->isPasswordProtected() ? "Private<br /><br/><strong>Draft Password:</strong> " . $DRAFT->draft_password : "Public"; ?></span></p>
					</div>
					<div style="width: 30%; float:right; text-align: right;">
						<p><img src="images/icons/<?php echo $DRAFT->draft_status; ?>.png" alt="<?php echo $DRAFT->draft_status; ?>" title="<?php echo $DRAFT->draft_status; ?>"/></p>
					</div>
					<p id="no-managers-msg" class="error"<?php if(HAS_MANAGERS) { ?> style="display: none;"<?php } ?>>*Before you can start your draft, you must <a href="draft.php?action=addManagers&did=<?php echo DRAFT_ID; ?>">add managers</a>.</p>
					<table id="managers-table" width="100%"<?php if(!HAS_MANAGERS) { ?> style="display: none;"<?php } ?>>
						<thead>
							<tr>
								<?php if($DRAFT->isUndrafted()) {?>
								<th width="100">&nbsp;</th>
								<?php } ?>
								<th>Manager Name</th>
								<th>Manager Email</th>
								<th width="85">Draft Order</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($MANAGERS as $manager) {
								$UPARROW = true;
								$DOWNARROW = true;

								if($manager->draft_order == 1)
									$UPARROW = false;
								if($manager->draft_order == LOWEST_ORDER)
									$DOWNARROW = false;
								?>
							<tr data-manager-id="<?php echo $manager->manager_id;?>">
								<?php if($DRAFT->isUndrafted()) {?>
								<td>
									<a href="manager.php?action=editManager&mid=<?php echo $manager->manager_id; ?>">Edit</a> |
									<!-- <a href="manager.php?action=deleteManager&mid=<?php echo $manager->manager_id; ?>">Delete</a>-->
									<span class="manager-delete-link"><a>Delete</a></span>
								</td>
								<?php } ?>
								<td><?php echo $manager->manager_name; ?></td>
								<td><?php echo $manager->manager_email; ?></td>
								<td>&nbsp;&nbsp;
								<?php if($DRAFT->isUndrafted()) {?>
									<span class="manager-move-link move-up up-on"></span>
									&nbsp;
									<span class="manager-move-link move-down down-on"></span>
								<?php } else { 
									echo $manager->draft_order;
								} ?>	
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</fieldset>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name; ?> - Functions</legend>
					<?php if($DRAFT->isUndrafted()) {?><p><strong><a href="draft.php?action=addManagers&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-plusthick"></span>Add Manager(s)</a></strong></p>
					<?php } ?><p><strong><a id="changeVisibility" href="#"><span class="phpdraft-icon ui-icon ui-icon-key"></span>Change Draft Visibility</a></strong></p>
					<?php if(!$DRAFT->isCompleted() && HAS_MANAGERS) { ?><p id="draft-status-link"><strong><a href="draft.php?action=changeStatus&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-play"></span>Change Draft Status</a></strong></p><?php } ?>
				</fieldset>
				<?php
				if($DRAFT->isInProgress() || $DRAFT->isCompleted()) {
				?>
				<fieldset>
					<legend>Recent Picks - Last 10</legend>
					<table width="100%">
						<tr>
							<th width="55">Rd #</th>
							<th width="55">Pick #</th>
							<th>Manager</th>
							<th>Player</th>
							<th width="55">Pos.</th>
							<th width="55">Team</th>
						</tr>
						<?php if(count($LAST_TEN_PICKS) == 0) {
							?><td colspan="5"><h2>No picks have been made yet.</h2></td><?php
						}else {
							foreach($LAST_TEN_PICKS as $player) { ?>
						<tr style="background-color:<?php echo $DRAFT->sports_colors[$player->position]; ?>;">
							<td><?php echo $player->player_round; ?></td>
							<td><?php echo $player->player_pick; ?></td>
							<td><?php echo $player->manager_name; ?></td>
							<td><?php if($player->hasName()) { echo $player->properName(); } else { echo "&nbsp;";}?></td>
							<td><?php echo $player->position; ?></td>
							<td><?php echo $player->team; ?></td>
						</tr>
							<?php }
						}?>
					</table>
				</fieldset>
				<?php } ?>
			</div>
			<?php require('includes/footer.php'); ?>
			<script src="js/draft.index.js" type="text/javascript"></script>
		</div>
		<div id="visibilityDialog">
			<p>Change whether or not this draft is viewable publicly. If you would like to make it private, you must provide a password.</p>
			<label for="draft_status">Draft Status:</label>
			<select id="draft_status" name="draft_status">
				<option value="1" <?php if($DRAFT->isPasswordProtected()) { echo " selected=\"selected\""; } ?>>Password Protected</option>
				<option value="0" <?php if(!$DRAFT->isPasswordProtected()) { echo " selected=\"selected\""; } ?>>Public</option>
			</select>
			<div id="passwordBox"<?php if(!$DRAFT->isPasswordProtected()) { echo " style=\" display: none;\""; }?>>
				<label for="draft_password">Draft Password:</label>
				<input type="text" id="draft_password" value="<?php echo $DRAFT->draft_password; ?>" /><br/>
				<label for="draft_password_confirm">Confirm Password:</label>
				<input type="text" id="draft_password_confirm" value="<?php echo $DRAFT->draft_password; ?>" /><br/>
			</div>
			<p id="visibilityError" class="errorDescription error"></p>
		</div>
	</body>
</html>