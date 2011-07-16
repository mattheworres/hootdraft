<?php
/*
 * view File for Draft Room
 *
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php require('meta.php'); ?>
	<script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function load_search() {
		var search_terms = $("#search_terms").val();
		$("#search_results").load('draft_search.php?action=load_search&draft_id=<?php echo $draft_id; ?>&search='+search_terms);
		};

		$(document).ready(function() {
		$("#search_terms").keydown(function(e) {
			if(e.keyCode == 13) {
			load_search();
			}
		});

		$("#submit_search").click(function() {
			load_search();
		});
		});
	</script>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('draft_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<p><?php echo $msg; ?></p>
		<p><label for="search_terms">Search for: </label> <input type="text" name="search_terms" id="search_terms" length="30" maxlength="30" /><br />
			<input type="submit" value="Search" id="submit_search" /></p>
		<div id="search_results"></div>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>