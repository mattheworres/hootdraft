<?php
/*
 * view File for Errors:
 *
 * To be included in a php-only page and have these variables setup already:
 *
 * $title - A title for the page
 * $msg - An error message, HTML if need be, to show user and inform them of error
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
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
		<h3><?php echo $title; ?></h3>
		<p class="error"><?php echo $msg; ?></p>
		<p class="success"><?php echo $success_msg; ?></p>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>