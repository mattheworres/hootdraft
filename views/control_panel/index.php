<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('meta.php');?>
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('/includes/header.php');
			require_once('models/manager_object.php');?>
			<div id="content-wide">
				<h3>Commissioner Control Panel</h3>
				<p>This is your control panel.  From here, you have access to running your entire web-based fantasy drafts.</p>
				<p>In order to get started, please choose to create a new draft or to manage an existing one below.</p>

				<fieldset>
					<legend>Commissioner Functions</legend>
					<p><strong><a href="control_panel.php?action=createDraft"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-plusthick"></span>Create a New Draft</a></strong> - Create a brand new draft from scratch, and then add managers to that draft afterwards</p>
					<p><strong><a href="control_panel.php?action=manageProfile"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-person"></span>Update Your User Profile</a></strong> - Change your login, password and name here for PHPDraft</p>
				</fieldset>

				<fieldset>
					<legend>Manage an Existing Draft</legend>
					<p>To begin managing a draft (either draft details, or editing managers, or editing players), select a draft below by clicking on its name.</p>
					<table width="100%">
						<tr>
							<th>&nbsp;</th>
							<th>Draft Name</th>
							<th>Draft Sport</th>
							<th># Managers</th>
							<th>Status</th>
						</tr>
						<?php
						$alt_row = false;

						foreach($DRAFTS as $draft) {
							$numberOfManagers = manager_object::getCountOfManagersByDraft($draft->draft_id);
							?>
							<tr<?php echo ($alt_row ? " style=\"background-color: #cccccc;\"" : "");?>>
								<td><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-<?php echo $draft->visibility; ?>"></span></td>
								<td><a href="draft.php?did=<?php echo $draft->draft_id;?>"><?php echo $draft->draft_name;?></a></td>
								<td><?php echo $draft->draft_sport;?></td>
								<td><?php echo $numberOfManagers;?></td>
								<td><?php echo $draft->getStatus();?></td>
							</tr>
							<?php
							$alt_row = $alt_row ? false : true;
						}
						?>
					</table>
				</fieldset>
			</div>
			<?php require('/includes/footer.php');;?>
		</div>
	</body>
</html>