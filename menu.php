	<?php require_once("login_fcns.php");?><div id="right_side">
        <h3>Navigation</h3>
        <?php if(isLoggedIn())
		{?><div class="featurebox_side">You are logged in as <strong><?php echo $_SESSION['username'];?></strong></div>
		<?php }?><p>Select a Draft</p>
        <div class='featurebox_side'>PHPDraft is currently being developed by a one-man programming machine (half man, half programmer, half bear-pig), but will eventually become an open source project!</div>
     </div>