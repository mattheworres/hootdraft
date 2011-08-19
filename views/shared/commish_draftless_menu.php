<div id="right_side">
	<h3>Draft Links</h3>
	 <?php if(CONTROL_PANEL_ACTION != "CREATE") {?><p><a href="control_panel.php?action=createDraft"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-plusthick"></span>Create Draft</a></p><?php } ?>
	 <p><a href="control_panel.php"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-star"></span>Control Panel Home</a></p>
	<div class='featurebox_side'>In order to add managers or start a draft, you must create or select a draft first.</div>
	<?php if($LOGGED_IN_USER->userAuthenticated()) {?><div class="featurebox_side">You are logged in as <strong><?php echo $LOGGED_IN_USER->Username; ?></strong></div>
	<?php }?>
</div>