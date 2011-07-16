<?php
global $draft_id;		//Use the globally-defined draft_id, which is passed in as a $_REQUEST var
if(!empty($draft_id)) {
	include_once('models/draft_model.php');
	require_once("login_fcns.php");
	?><div id="right_side">
	<h3>Navigation</h3>
	<p><a href="control_panel.php?action=manageDrafts">&larr; Select a Draft</a></p>
	<?php if(is_undrafted($draft_id)) {?><p><a href="comm_edit_draft.php?did=<?php echo $draft_id; ?>">Edit Draft Details</a></p>
	<?php } if($currentFile != "comm_manage_draft.php") {?><p><a href="comm_manage_draft.php?did=<?php echo $draft_id; ?>">Manage Draft</a></p>
	<?php } ?><p><a href="comm_delete_draft.php?did=<?php echo $draft_id; ?>">Remove This Draft</a></p>
	<p><a href="comm_draft_room.php?draft_id=<?php echo $draft_id; ?>">Enter the Draft Room &rarr;</a></p>
	<?php if(isLoggedIn()) {?><div class="featurebox_side">You are logged in as <strong><?php echo $_SESSION['username']; ?></strong></div>
		<?php }?>
</div><?php }//This will hide the menu if we're given a bogus/bad draft ID?>