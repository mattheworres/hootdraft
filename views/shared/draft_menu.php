<?php
if(DRAFT_ID > 0) {
	?><div id="right_side">
	<h3>Navigation</h3>
	<p><a href="draft.php?did=<?php echo DRAFT_ID; ?>"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-home"></span><strong>Draft Home</strong></a></p>
	<?php if($DRAFT->isUndrafted()) {?><p><a href="draft.php?action=editDraft&did=<?php echo DRAFT_ID; ?>"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-note"></span>Edit Draft Details</a></p>
	<?php } ?><p><a href="draft.php?action=deleteDraft&did=<?php echo DRAFT_ID; ?>"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-trash"></span>Remove This Draft</a></p>
	<?php if(!$DRAFT->isUndrafted()) { ?><p><a href="draft_room.php?action=home&did=<?php echo DRAFT_ID; ?>"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-circle-check"></span>Enter the Draft Room</a></p><?php } ?>
	<p><a href="control_panel.php?action=manageDrafts"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-circle-arrow-w"></span>Select Another Draft</span></a></p>
	<?php if($owner->userAuthenticated()) {?><div class="featurebox_side"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-person"></span>You are logged in as <strong><?php echo $owner->user_name; ?></strong></div>
		<?php }?>
</div><?php } ?>
