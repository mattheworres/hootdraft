<?php require('check_login.php');?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
		<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		require_once('cleanstring.php');
		require_once('models/draft_model.php');
		require_once('models/draft_object.php');
		
		//TODO: REMOVE THIS, just need to get a sense for the object model:
		$DRAFT = new draft_object();
		
		if(DRAFT_ID == 0)
		require('comm_menu.php');
		else
		require('comm_draft_menu.php');?>
			<div id="content">
				<h3>Manage <?php echo $DRAFT->draft_name . " (" . $DRAFT->draft_sport . ")";?></h3>
				<p>Select your option below to begin managing this draft, or to begin/continue the draft process, enter the Draft Room now!</p>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name;?> - Current Status</legend>
					<div style="width: 70%; float:left;">
						<p><strong>Sport: </strong> <?php echo $DRAFT->draft_sport;?></p>
						<p><strong>Drafting Style: </strong> <?php echo $DRAFT->draft_style;?></p>
						<p><strong># of Rounds: </strong> <?php echo $DRAFT->draft_rounds;?></p>
						<p><strong>Status: </strong> <?php echo $DRAFT->draft_status;?> </p>
						<?php if($DRAFT->isCompleted()) {?><p><strong>Total Draft Duration: </strong><?php echo $DRAFT->getDraftDuration() . "</p>";}?>
						<p><strong>Draft Visibility: </strong> <?php echo $DRAFT->isPasswordProtected() ? "Private<br /><strong>Draft Password:</strong> " . $DRAFT->draft_password : "Public";?></p>
					</div>
					<div style="width: 30%; float:right; text-align: right;">
						<p><img src="images/icons/<?php echo $DRAFT->draft_status;?>.png" alt="<?php echo $DRAFT->draft_status;?>" title="<?php echo $DRAFT->draft_status;?>"/></p>
					</div>
					<?php if($manager_num == 0) {
							echo "<p class=\"error\">*Before you can start your draft, you must <a href=\"comm_add_mgrs.php?did=".$draft_id."\">add managers</a>.</p>\n";
						}else { ?>

						<table width="100%">
							<tr>
						<?php if($DRAFT->isUndrafted()) {?><th width="100">&nbsp;</th>
			<?php } ?><th>Manager Name</th>
								<th>Manager Team</th>
					<th width="85">Draft Order</th>
							</tr>
						<?php
						while($manager_row = mysql_fetch_array($manager_result)) {
						$uparrow_on = true;
						$downarrow_on = true;
						if($manager_row['draft_order'] == 1)
							$uparrow_on = false;
						if($manager_row['draft_order'] == $lowest_order)
							$downarrow_on = false;
			?>
							<tr>
			<?php if($DRAFT->isUndrafted()) {?><td><a href="comm_edit_mgr.php?did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>">Edit</a> |
									<a href="comm_delete_mgr.php?did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>">Delete</a></td>
				<?php } ?><td><?php echo $manager_row['manager_name'];?></td>
								<td><?php echo $manager_row['team_name'];?></td>
					<td>
							<?php echo $manager_row['draft_order'] . "&nbsp;&nbsp;";?>
			<?php if($uparrow_on && $DRAFT->isUndrafted()) {?>
					<a href="comm_draft_order.php?action=up&did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>"><img src="images/icons/ArrowUp.png" alt="Move Up" border="0" /></a>
				<?php }else {?>
					<img src="images/icons/ArrowUp_off.png" alt="Move Up"  border="0"/>
								<?php }
							echo "&nbsp;";
			if($downarrow_on && $DRAFT->isUndrafted()) {?>
					<a href="comm_draft_order.php?action=down&did=<?php echo $draft_id;?>&mid=<?php echo $manager_row['manager_id'];?>"><img src="images/icons/ArrowDown.png" alt="Move Up"  border="0"/></a>
				<?php }else {?>
					<img src="images/icons/ArrowDown_off.png" alt="Move Up"  border="0"/>
				<?php }?>
					</td>
							</tr>
						<?php
					} ?></table>
			<?php } ?>
				</fieldset>
				<fieldset>
					<legend><?php echo $DRAFT->draft_name;?> - Functions</legend>
					<?php if($DRAFT->isUndrafted()) {?><p><strong><a href="comm_add_mgrs.php?did=<?php echo $draft_id;?>">Add Manager(s)</a></strong></p>
					<?php } ?><p><strong><a href="comm_edit_draft_pass.php?did=<?php echo $draft_id;?>">Change Draft Visibility</a></strong></p>
					<?php if(!$DRAFT->isCompleted() && $manager_num > 0) {?><p><strong><a href="comm_edit_draft_status.php?did=<?php echo $draft_id;?>">Change Draft Status</a></strong></p><?php } ?>
				</fieldset>
			</div>
<?php require('footer.php'); ?>
		</div>
	</body>
</html>