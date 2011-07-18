<?php require('check_login.php'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
		<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php
		require_once('models/draft_model.php');
		require_once('models/manager_object.php');

		require('comm_menu.php'); ?>
			<div id="content">
		<h3>Select a Draft</h3>
				<p>To begin managing a draft (either draft details, or editing managers, or editing players), select a draft below by clicking on its name.</p>
				<table width="700">
					<tr>
						<th>Draft Name</th>
						<th>Draft Sport</th>
						<th># Managers</th>
						<th>Status</th>
					</tr>
					<?php
					$alt_row = true;
					
					foreach($DRAFTS as $draft) {
							$numberOfManagers = manager_object::getCountOfManagersByDraftId($draft->draft_id); ?>
					<tr<?php echo ($alt_row ? " background-color=\"#cccccc\"" : ""); ?>>
						<td><a href="draft.php?did=<?php echo $draft->draft_id; ?>"><?php echo $draft->draft_name; ?></a></td>
						<td><?php echo $draft->draft_sport; ?></td>
						<td><?php echo $numberOfManagers; ?></td>
						<td><?php echo $draft->draft_status; ?></td>
					</tr>
				<?php
				if($alt_row)
				$alt_row = false;
				else
				$alt_row = true;
			}//foreach ?>
				</table>
			</div>
<?php require('footer.php'); ?>
		</div>
	</body>
</html>