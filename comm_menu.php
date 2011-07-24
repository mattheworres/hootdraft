<?php require_once("login_fcns.php"); ?><div id="right_side">
	<h3>Draft Links</h3>
	 <?php if(CONTROL_PANEL_ACTION != "CREATE") {?><p><a href="control_panel.php?action=createDraft"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-plusthick"></span>Create Draft</a></p><?php } ?>
	 <?php if(CONTROL_PANEL_ACTION != "MANAGE") {?><p><a href="control_panel.php?action=manageDrafts"><span style="display: inline-block; vertical-align: middle; margin-right: 2px;" class="ui-icon ui-icon-circle-arrow-w"></span>Select a Draft</a></p><?php } ?>
	<div class='featurebox_side'>In order to add managers or start a draft, you must create or select a draft first.</div>
	<?php if(isLoggedIn()) {?><div class="featurebox_side">You are logged in as <strong><?php echo $_SESSION['username']; ?></strong></div>
	<?php }?>
</div>