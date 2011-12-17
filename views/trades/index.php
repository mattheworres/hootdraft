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
			<h3><?php echo $DRAFT->draft_name; ?> - Enter Trade</h3>
			<p>Once two managers have decided on exactly what to trade amongst themselves, this page will allow you to enter it into the draft immediately.</p>
			<p><strong>Note:</strong> Each manager must receive at least one asset in return.</p>
			<p><strong>To get started, choose the managers involved in this trade:</strong></p>
			<form id="trade_box">
				<input type="hidden" id="did" name="did" value="<?php echo DRAFT_ID; ?>" />
				<div class="manager_1">
					<h4>Manager One</h4><br/>
					<select id="manager1" name="manager1" class="manager_select" data-manager-id="1">
						<option>(choose a manager)</option>
						<?php foreach($MANAGERS as $manager) {/*@var $manager manager_object */ ?>
						<option value="<?PHP echo $manager->manager_id; ?>"><?php echo $manager->manager_name; ?></option>
						<?php } ?>
					</select>
					<div id="manager1Players" class="managerPlayers"></div>
				</div>
				<div class="manager_2">
					<h4>Manager Two</h4><br/>
					<select id="manager2" name="manager2" class="manager_select" data-manager-id="2">
						<option>(choose a manager)</option>
						<?php foreach($MANAGERS as $manager) {/*@var $manager manager_object */ ?>
						<option value="<?PHP echo $manager->manager_id; ?>"><?php echo $manager->manager_name; ?></option>
						<?php } ?>
					</select>
					<div id="manager2Players" class="managerPlayers"></div>
				</div>
			</form>
			<div class="button_box">
				<input type="button" id="submit" value="Enter Trade" disabled="disabled" />
			</div>
			<p class="errorDescription error">There was an error, please try again.</p>
		</div>
		<?php require('includes/footer.php'); ?>
		<script src="js/jquery.form.js" type="text/javascript"></script>
		<script src="js/json.parse.min.js" type="text/javascript"></script>
		<script src="js/draft.trades.js" type="text/javascript"></script>
	</div>
	</body>
</html>