	<?php require_once("login_fcns.php");?><div id="right_side">
        <h3>Navigation</h3>
        <?php if(isLoggedIn())
		{?><div class="featurebox_side">You are logged in as <strong><?php echo $_SESSION['username'];?></strong></div>
		<?php }?><p>Select a Draft</p>
        <div class='featurebox_side'>PHPDraft is developed by a one-man programming machine (half man, half programmer, half bear-pig), and is currently an <a href="http://sourceforge.net/projects/phpdraft" target="_blank">open source project</a>! Check out the project page for more details!</div>
     </div>