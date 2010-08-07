<?php
/*
 * Template File for Draft Room
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
	    function bind_selection() {
		$("#round").change(function() {
		    var round_val = $("#round").val();
		   $("#selection").load('comm_draft_picks.php?action=get_round_picks&draft_id=<?php echo $draft_id;?>&round='+round_val, function() {bind_selection();});
		});
	    };

	    $(document).ready(function() {
		bind_selection();
	    });
	</script>

    </head>
    <body>
	<div id="page_wrapper">
	    <?php require('header.php'); ?>

	    <?php require('comm_draft_room_menu.php'); ?>
	    <div id="content">
		<h3><?php echo $title;?></h3>
		<p class="success"><?php echo $msg;?></p>
		<p class="error"><?php echo $err_msg;?></p>
		<fieldset>
		    <legend>Select a Pick to Edit</legend>
		    <form action="comm_draft_picks.php" method="post">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="draft_id" value="<?php echo $draft_id;?>" />
			<p>To edit a pick, select a round first, and then all editable picks will show up. Select your pick and hit "Edit This Pick" to continue.</p>
			<div id="selection">
			    <p><label for="round">Round*:</label>
			    <select name="round" id="round">
				<option value="">(select a round)</option>
				<?php for($i = 1; $i <= $rounds; $i++) {
				    ?><option value="<?php echo $i;?>">Round <?php echo $i;?></option>
				<?php } ?>
			    </select></p>
			    <p><label for="pick_id">Editable Picks*:</label>
				<select name="pick_id">
				    <option value="-1">(select a round)</option>
				</select></p>
			</div>
			<p><input type="submit" name="submit" class="button" value="Edit This Pick" /></p>
			<p class="error">*Required</p>
		    </form>
		</fieldset>
	    </div>
	    <?php require('footer.php'); ?>
	</div>
    </body>
</html>