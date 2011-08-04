<div id="right_side">
	<h3>Navigation</h3>
	<?php if($owner->userAuthenticated()) {?><div class="featurebox_side">You are logged in as <strong><?php echo $owner->user_name;?></strong></div>
	<?php }?><p><a href="index.php?action=select">Select a Draft</a></p>
	<div class='featurebox_side'>PHPDraft is developed by a one-man programming machine (half man, half programmer, half bear-pig), and is currently an <a href="http://sourceforge.net/projects/phpdraft" target="_blank">open source project</a>! Check out the project page for more details!</div>
</div>