<?php
global $draft_id;		//Use the globally-defined draft_id, which is passed in as a $_REQUEST var
if(!empty($draft_id)) {
	include_once('models/draft_model.php');
	require_once("login_fcns.php");
	?><div id="right_side">
	<h3>Navigation</h3>
	 <?php if($currentFile != "comm_draft_room.php") {?><h4><a href="comm_draft_room.php?draft_id=<?php echo $draft_id;?>">Main Draft Room</a></h4><?php } ?>
	<p><a href="comm_draft_picks.php?action=add&draft_id=<?php echo $draft_id;?>">Make a Pick</a></p>
	<p><a href="comm_draft_picks.php?action=select_edit&draft_id=<?php echo $draft_id;?>">Edit a Pick</a></p>
	<p><a href="comm_manage_draft.php?did=<?php echo $draft_id;?>">&larr; Back to Manage Page</a></p>
</div><?php }//This will hide the menu if we're given a bogus/bad draft ID?>