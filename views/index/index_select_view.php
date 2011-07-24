<?php session_start(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('menu.php'); ?>
		<div id="content">
		<h3>Choose a Draft</h3>
		<p>To get started, go ahead and click on the name of a draft below.  If a lock icon shows up beside a draft, that means you'll have to get the password from your commissioner*</p>
		<table width="100%">
			<tr>
			<th width="16">&nbsp;</th>
			<th>Draft Name</th>
			<th>Sport</th>
			<th>Draft Status</th>
			</tr>
			<?php if($drafts->number_of_drafts == 0) { ?><tr><td colspan="4"><h2>There are currently no drafts available.</h2></td></tr><?php
			}else {
			foreach($drafts->draft_objects as $draft) {?><tr>
				<td><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-<?php echo $draft->visibility; ?>"></span></td>
			<?php if(!$draft->isUndrafted()) {?><td><a href="draft_main.php?draft_id=<?php echo $draft->draft_id; ?>"><?php echo $draft->draft_name; ?></a></td>
						<?php } else {?><td><?php echo $draft->draft_name; ?></td><?php }?>
			<td><?php echo $draft->draft_sport; ?></td>
			<td><?php echo $draft->getStatus(); ?></td>
			</tr>
				<?php }
			}?>
		</table>
		<p style="font-size: 80%;">*Cookies must be enabled</p>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>