<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php require('/includes/meta.php');?>
		<link href="css/draft.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<div id="page_wrapper">
			<?php require('/includes/header.php');
			require('/views/shared/draft_menu.php');?>
			<div id="content">
				<input type="hidden" id="draft_id" value="<?php echo DRAFT_ID;?>" />
				<fieldset>
					<legend>Add Managers</legend>
					<p>Add multiple managers to your draft using this form. To add more rows, click on the green plus icon at the bottom. To remove rows, click on the red X of that row.  Below you can see a list of all current managers for reference.</p>
					<table id="add-managers-table" width="100%">
						<th width="40" class="center"><span id="addManagerButton"><img src="images/icons/add.png" alt="Add a Manager row"/></span></th>
						<th>Manager Name*</th>
						<th>Manager Email</th>
						<?php foreach($MANAGERS as $manager) { ?>
						<tr class="data-row">
							<td><span class="removeManagerButton">(&mdash;)</span></td>
							<td>
								<input type="text" class="manager_info manager_name" value="<?php echo $manager->manager_name; ?>" />
							</td>
							<td>
								<input type="text" class="manager_info manager_email" value="<?php echo $manager->manager_email; ?>"/>
							</td>
						</tr>
						<?php } ?>
						<tr id="last-row">
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td><input type="button" value="Add Managers" id="addManagers"/></td>
						</tr>
					</table>
					<p class="errorDescription error">One or more of the fields above have errors in them. Please correct the highlighted fields and try again.</p>
					<br/><br/>
					<table id="current-managers-table" class="alt_rows" width="100%">
						<th width="40">&nbsp;</th>
						<th>Manager Name</th>
						<th>Manager Email</th>
						<?php foreach($CURRENT_MANAGERS as $manager) { ?><tr class="current-row">
							<td>&nbsp;</td>
							<td class="current-manager-name"><?php echo $manager->manager_name; ?></td>
							<td class="current-manager-email"><?php echo $manager->manager_email; ?></td>
						</tr>
						<?php } ?>
					</table>
				</fieldset>
			</div>
			<?php require('/includes/footer.php');;?>
			<script src="js/draft.add_managers.js" type="text/javascript"></script>
		</div>
	</body>
</html>