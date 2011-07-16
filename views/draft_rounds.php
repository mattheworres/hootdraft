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
		var round;
		$("#round").hide();

		$(document).ready(function() {
		$("#round_select").change(function() {
			round = $(this).val();
			$("#round").load('draft_rounds.php?action=load_round&draft_id=<?php echo $draft_id; ?>&round='+round);
		});
		$("#refresh").click(function() {
			round = $("#round_select").val();
			$("#round").load('draft_rounds.php?action=load_round&draft_id=<?php echo $draft_id; ?>&round='+round);
		})
		});
	</script>
	</head>
	<body>
	<div id="page_wrapper">
		<?php require('header.php'); ?>

		<?php require('draft_menu.php'); ?>
		<div id="content">
		<h3><?php echo $title; ?></h3>
		<p>Select a round from the box below to see the draft picks made thus far in that round.</p>
		<p><label for="round_select">Select Round:</label>
			<select name="round_select" id="round_select">
			<option>(select a round)</option>
			<?php for($i = 1; $i <= $number_of_rounds; $i++) {
				?><option value="<?php echo $i; ?>">Round #<?php echo $i; ?></option>
				<?php } ?>
			</select>
			<?php if($draft_row['draft_status'] != "complete") {?>&nbsp;<a href="javascript:void();" id="refresh"><img src="images/icons/refresh.png" class="small_link" alt="Refresh Draft Picks" />&nbsp;(Refresh)</a><?php } ?></p>
		<div id="round"></div>
		</div>
		<?php require('footer.php'); ?>
	</div>
	</body>
</html>