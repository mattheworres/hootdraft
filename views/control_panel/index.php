<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<div id="content-wide">
		<h3>Commissioner Control Panel</h3>
		<p>This is your control panel.  From here, you have access to running your entire web-based fantasy drafts.</p>
		<p>In order to get started, please choose to create a new draft or to manage an existing one below.</p>

		<fieldset>
			<legend>Draft Management</legend>
			<div>
				<p><strong><a href="control_panel.php?action=createDraft"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-plusthick"></span>Create a New Draft</a></strong> - Create a brand new draft from scratch, and then add managers to that draft afterwards</p>
				<p><strong><a href="control_panel.php?action=manageDrafts"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-gear"></span>Manage an Existing Draft</a></strong> - Choose an existing draft from a list and manage it.  This includes adding team managers and the draft room itself!</p>
			</div>
		</fieldset>

		<fieldset>
			<legend>User Management</legend>
			<div>
				<p><strong><a href="control_panel.php?action=manageProfile"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-person"></span>Update Your User Profile</a></strong> - Change your login, password and name here for PHPDraft</p>
			</div>
		</fieldset>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>