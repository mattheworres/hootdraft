<?php
if(DRAFT_ID > 0) {
	?><div id="right_side">
	<h3>Navigation</h3>
	<p><a href="control_panel.php?action=manageDrafts">&larr; Select a Draft</a></p>
	<p><a href="draft.php?did=<?php echo DRAFT_ID; ?>">Draft Home</a></p>
	<?php if($DRAFT->isUndrafted()) {?><p><a href="comm_edit_draft.php?did=<?php echo DRAFT_ID; ?>">Edit Draft Details</a></p>
	<?php } ?><p><a href="comm_delete_draft.php?did=<?php echo DRAFT_ID; ?>">Remove This Draft</a></p>
	<p><a href="comm_draft_room.php?draft_id=<?php echo DRAFT_ID; ?>">Enter the Draft Room &rarr;</a></p>
	<?php if($owner->userAuthenticated()) {?><div class="featurebox_side">You are logged in as <strong><?php echo $owner->user_name; ?></strong></div>
		<?php }?>
</div><?php } ?>
