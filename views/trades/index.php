<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('includes/meta.php'); ?>
		<link href="css/trades.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('includes/header.php'); ?>

		<?php require('views/shared/draft_room_menu.php'); ?>
		<div id="content">
			<input type="hidden" id="did" value="<?php echo DRAFT_ID; ?>" />
			<h3><?php echo $DRAFT->draft_name; ?> - Enter Trade</h3>
			<p>Once two managers have decided on exactly what to trade amongst themselves, this page will allow you to enter it into the draft immediately.</p>
			<p><strong>Note:</strong> Each manager must receive at least one asset in return.</p>
			<p><strong>To get started, choose a manager on both sides below:</strong></p>
			<div class="manager_1">
				<h4>Manager One</h4><br/>
				<select id="manager1" class="manager_select">
					<option>(choose a manager)</option>
					<?php foreach($MANAGERS as $manager) {/*@var $manager manager_object */ ?>
					<option value="<?PHP echo $manager->manager_id; ?>"><?php echo $manager->manager_name; ?></option>
					<?php } ?>
				</select>
				<div class="managerPlayers"></div>
			</div>
			<div class="manager_2">
				<h4>Manager Two</h4><br/>
				<select id="manager2" class="manager_select">
					<option>(choose a manager)</option>
					<?php foreach($MANAGERS as $manager) {/*@var $manager manager_object */ ?>
					<option value="<?PHP echo $manager->manager_id; ?>"><?php echo $manager->manager_name; ?></option>
					<?php } ?>
				</select>
				<div class="managerPlayers"></div>
			</div>
			<p class="errorDescription error">There was an error, please try again.</p>
			<!-- TODO: Finish writing JS interaction, add jQuery multiselect to this mix, test all DB stuff -->
		</div>
		<?php require('includes/footer.php'); ?>
		<script src="js/draft.trades.js" type="text/javascript"></script>
	</div>
	</body>
</html>