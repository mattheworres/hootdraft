<?php
if(DRAFT_ID > 0) {
	?><div id="right_side">
	<h3>Navigation</h3>
	<p><a href="draft.php?did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-home"></span><strong>Draft Home</strong></a></p>
	<?php if($DRAFT->isUndrafted()) {?><p><a href="draft.php?action=editDraft&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-note"></span>Edit Draft Details</a></p>
	<?php } ?><p><a href="draft.php?action=deleteDraft&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-trash"></span>Remove This Draft</a></p>
	<?php if(!$DRAFT->isUndrafted()) { ?><p><a href="draft_room.php?action=home&did=<?php echo DRAFT_ID; ?>"><span class="phpdraft-icon ui-icon ui-icon-circle-check"></span>Enter the Draft Room</a></p><?php } ?>
	<p><a href="control_panel.php?action=manageDrafts"><span class="phpdraft-icon ui-icon ui-icon-circle-arrow-w"></span>Select Another Draft</span></a></p>
	<?php if($LOGGED_IN_USER->userAuthenticated()) {?><div class="featurebox_side"><span class="phpdraft-icon ui-icon ui-icon-person"></span>You are logged in as <strong><?php echo $LOGGED_IN_USER->Username; ?></strong></div>
		<?php }?>
</div><?php } ?>
