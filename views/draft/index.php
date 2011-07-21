<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php'); ?>
		<link href="css/draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
<?php 
require('header.php');
require_once('cleanstring.php');
require_once('models/draft_model.php');
require_once('models/draft_object.php');

require('views/shared/draft_menu.php'); ?>
			<div id="content">
				<h3>Manage <?php echo $DRAFT->draft_name; ?> (<?php echo $DRAFT->draft_sport; ?>)</h3>
				<p>Select your option below to begin managing this draft, or to begin/continue the draft process, enter the Draft Room now!</p>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name; ?> - Current Status</legend>
					<div style="width: 70%; float:left;">
						<p><strong>Sport: </strong> <?php echo $DRAFT->draft_sport; ?></p>
						<p><strong>Drafting Style: </strong> <?php echo $DRAFT->draft_style; ?></p>
						<p><strong># of Rounds: </strong> <?php echo $DRAFT->draft_rounds; ?></p>
						<p><strong>Status: </strong> <?php echo $DRAFT->draft_status; ?> </p>
						<?php if($DRAFT->isCompleted()) { ?><p><strong>Total Draft Duration: </strong><?php echo $DRAFT->getDraftDuration(); ?></p><?php } ?>
						<p><strong>Draft Visibility: </strong> <?php echo $DRAFT->isPasswordProtected() ? "Private<br /><strong>Draft Password:</strong> " . $DRAFT->draft_password : "Public"; ?></p>
					</div>
					<div style="width: 30%; float:right; text-align: right;">
						<p><img src="images/icons/<?php echo $DRAFT->draft_status; ?>.png" alt="<?php echo $DRAFT->draft_status; ?>" title="<?php echo $DRAFT->draft_status; ?>"/></p>
					</div>
					<?php if(!HAS_MANAGERS) { ?>
					<p class="error">*Before you can start your draft, you must <a href=\"comm_add_mgrs.php?did=<?php echo DRAFT_ID; ?>">add managers</a>.</p>
						<?php }else { ?>
					<table id="managers-table" width="100%">
						<thead>
							<tr>
								<?php if($DRAFT->isUndrafted()) {?>
								<th width="100">&nbsp;</th>
								<?php } ?>
								<th>Manager Name</th>
								<th>Manager Team</th>
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
									<a href="manager.php?action=deleteManager&mid=<?php echo $manager->manager_id; ?>">Delete</a>
								</td>
								<?php } ?>
								<td><?php echo $manager->manager_name; ?></td>
								<td><?php echo $manager->team_name; ?></td>
								<td>&nbsp;&nbsp;
								<?php if($DRAFT->isUndrafted()) {?>
									<span class="manager-move-link move-up up-on"></span>
									&nbsp;
									<span class="manager-move-link move-down down-on"></span>
								<?php } ?>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
			<?php } ?>
				</fieldset>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name; ?> - Functions</legend>
					<?php if($DRAFT->isUndrafted()) {?><p><strong><a href="comm_add_mgrs.php?did=<?php echo DRAFT_ID; ?>">Add Manager(s)</a></strong></p>
					<?php } ?><p><strong><a href="comm_edit_draft_pass.php?did=<?php echo DRAFT_ID; ?>">Change Draft Visibility</a></strong></p>
					<?php if(!$DRAFT->isCompleted() && HAS_MANAGERS) {?><p><strong><a href="comm_edit_draft_status.php?did=<?php echo DRAFT_ID; ?>">Change Draft Status</a></strong></p><?php } ?>
				</fieldset>
			</div>
			<?php require('footer.php'); ?>
			<script src="js/draft.index.js" type="text/javascript"></script>
		</div>
	</body>
</html>