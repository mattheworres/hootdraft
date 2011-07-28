<?php
if(DRAFT_ID == 0) {
	?><div id="right_side">
	<h3>Navigation</h3>
	 <h4><a href="draft_room.php?did=<?php echo DRAFT_ID; ?>"><strong>Main Draft Room</strong></a></h4>
	<p><a href="comm_draft_picks.php?action=add&draft_id=<?php echo DRAFT_ID; ?>">Make a Pick</a></p>
	<p><a href="comm_draft_picks.php?action=select_edit&draft_id=<?php echo DRAFT_ID; ?>">Edit a Pick</a></p>
	<p><a href="comm_manage_draft.php?did=<?php echo DRAFT_ID; ?>">&larr; Back to Manage Page</a></p>
</div><?php } ?>