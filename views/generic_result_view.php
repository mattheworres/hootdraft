<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('/includes/header.php'); ?>

		<?php require('/views/shared/main_menu.php'); ?>
		<div id="content-wide">
		<h3><?php echo PAGE_HEADER; ?></h3>
		<p class="<?php echo P_CLASS; ?>"><?php echo PAGE_CONTENT; ?></p>
		<br/>
		</div>
		<?php require('/includes/footer.php');; ?>
	</div>
	</body>
</html>