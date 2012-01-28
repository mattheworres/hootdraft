<?php 
	$active = ' id="active"';
	$current = ' id="current" name="current"';
	
	$is_authenticated = $LOGGED_IN_USER->userAuthenticated();
	$commish_name = $COMMISH->Name;
	
	$index_active = ACTIVE_TAB == 'INDEX' ?  $active : '';
	$index_current = ACTIVE_TAB == 'INDEX' ? $current : '';
	$draft_active = ACTIVE_TAB == 'DRAFT_CENTRAL' ? $active : '';
	$draft_current = ACTIVE_TAB == 'DRAFT_CENTRAL' ? $current : '';
	$login_active = ACTIVE_TAB == 'LOGIN' ? $active : '';
	$login_current = ACTIVE_TAB == 'LOGIN' ? $current : '';
	$cp_active = ACTIVE_TAB == 'CONTROL_PANEL' ? $active : '';
	$cp_current = ACTIVE_TAB == 'CONTROL_PANEL' ? $current : '';
	$logout_active = ACTIVE_TAB == 'LOGOUT' ? $active : '';
	$logout_current = ACTIVE_TAB == 'LOGOUT' ? $current : '';
	$help_active = ACTIVE_TAB == 'HELP' ? $active : '';
	$help_current = ACTIVE_TAB == 'HELP' ? $current : '';
?>
<div id="header_wrapper">
	<div id="header">
		<div class="left">
			<h1>PHP<span class="secondary">Draft</span></h1>
			<h2>Commissioner <?php echo $commish_name;?></h2>
		</div>
		<div class="right">
			<h3>Web-Based Fantasy Draft Software</h3>
		</div>
	</div>
	<div id="navcontainer">
		<ul id="navlist">
			<li<?php echo $index_active; ?>><a href="index.php"<?php echo $index_current; ?>>Welcome!</a></li>
			<li<?php echo $draft_active; ?>><a href="index.php?action=select"<?php echo $draft_current; ?>>Draft Central</a></li>
			<?php  if($is_authenticated === true) { ?>
			<li<?php echo $cp_active; ?>><a class="commish" href="control_panel.php?action=home"<?php echo $cp_current; ?>>Control Panel</a></li>
			<li<?php echo $logout_active; ?>><a href="logout.php"<?php echo $logout_current; ?>>Log Out</a></li>
			<?php } else if($is_authenticated === false) { ?>
			<li<?php echo $login_active; ?>><a class="commish" href="login.php"<?php echo $login_current; ?>>Commissioner Login</a></li>
			<?php } ?>
			<li<?php echo $help_active; ?>><a href="help.php"<?php echo $help_current; ?>>Help</a></li>
		</ul>
	</div>
</div>