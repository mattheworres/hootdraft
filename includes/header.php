<div id="header_wrapper">
	<div id="header">
	<div style="width:50%; float: left;">
		<h1>PHP<font color="#FFDF8C">Draft</font></h1>
		<h2>Commissioner <?php echo $COMMISH->Name;?></h2>
	</div>
	<div style="width:50%; float: right;">
		<h3>Web-Based Fantasy Draft Software</h3>
	</div>
	</div>
	<div id="navcontainer">
	<ul id="navlist">
			<li<?php if(ACTIVE_TAB == 'INDEX') echo ' id="active"'; ?>><a href="index.php"<?php if(ACTIVE_TAB == 'INDEX') echo ' id="current" name="current"'; ?>>Welcome!</a></li>
		<li<?php if(ACTIVE_TAB == 'DRAFT_CENTRAL') echo ' id="active"'; ?>><a href="index.php?action=select"<?php if(ACTIVE_TAB == 'DRAFT_CENTRAL') echo ' id="current" name="current"'; ?>>Draft Central</a></li>
			<?php  if(!$LOGGED_IN_USER->userAuthenticated()) { ?>
			<li<?php if(ACTIVE_TAB == 'LOGIN') echo ' id="active"'; ?>><a href="login.php"<?php if(ACTIVE_TAB == 'LOGIN') echo ' id="current" name="current"'; ?>>Commissioner Login</a></li>
	<?php
		}else {?><li<?php if(ACTIVE_TAB == 'CONTROL_PANEL') echo ' id="active"'; ?>><a href="control_panel.php?action=home"<?php if(ACTIVE_TAB == 'CONTROL_PANEL') echo ' id="current" name="current"'; ?>>Control Panel</a></li>
		<li<?php if(ACTIVE_TAB == 'LOGOUT') echo ' id="active"'; ?>><a href="logout.php"<?php if(ACTIVE_TAB == 'LOGOUT') echo ' id="current" name="current"'; ?>>Log Out</a></li>
<?php	   } ?>
	</ul>
	</div>
</div>