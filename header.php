<?php
include_once("login_fcns.php");
?><div id="header_wrapper">
	<div id="header">
	<div style="width:50%; float:left;">
		<h1>PHP<font color="#FFDF8C">Draft</font></h1>
		<h2>Commissioner <?php echo $owner->public_name;?></h2>
	</div>
	<div style="width:50%; float:right;">
		<h3>Web-Based Fantasy Draft Software</h3>
	</div>
	</div>
	<div id="navcontainer">
	<ul id="navlist"><?php
		//New tab-selection method relies on each Presenter to set the $ACTIVE_TAB variable.
		if(defined("ACTIVE_TAB")/*!empty($ACTIVE_TAB)*/) {?>
			<li<?php if(ACTIVE_TAB == 'INDEX') echo " id=\"active\"";?>><a href="index.php"<?php if(ACTIVE_TAB == 'INDEX') echo " id=\"current\" name=\"current\"";?>>Welcome!</a></li>
		<li<?php if(ACTIVE_TAB == 'DRAFT_CENTRAL') echo " id=\"active\"";?>><a href="index.php?q=select"<?php if(ACTIVE_TAB == 'DRAFT_CENTRAL') echo " id=\"current\" name=\"current\"";?>>Draft Central</a></li>
			<?php  if(!isLoggedIn()) {//if the user is not authenticated?>
			<li<?php if(ACTIVE_TAB == 'LOGIN') echo " id=\"active\"";?>><a href="login.php"<?php if(ACTIVE_TAB == 'LOGIN') echo " id=\"current\" name=\"current\"";?>>Commissioner Login</a></li>
	<?php
		}else {?><li<?php if(ACTIVE_TAB == 'CONTROL_PANEL') echo " id=\"active\"";?>><a href="control_panel.php?action=home"<?php if(ACTIVE_TAB == 'CONTROL_PANEL') echo " id=\"current\" name=\"current\"";?>>Control Panel</a></li>
		<li<?php if(ACTIVE_TAB == 'LOGOUT') echo " id=\"active\"";?>><a href="logout.php"<?php if(ACTIVE_TAB == 'LOGOUT') echo " id=\"current\" name=\"current\"";?>>Log Out</a></li>
<?php	   }
		}else {
			/*
			 *NOTE: This logic is now considered legacy, and should be removed before the next release.
			 *The UPDATE HEADER LOGIC list item text will show up as red text on any page that is not correctly using the new logic.
			 */
			$currentFile = $_SERVER["SCRIPT_NAME"];
		$parts = Explode('/', $currentFile);
		$currentFile = $parts[count($parts) - 1];
		$first_four_chars = substr($currentFile, 0, 4);
			$query_string = $_GET['q'];?>
			<li style="color: red;">UPDATE HEADER LOGIC</li>
			<li<?php if($currentFile == 'index.php' && (empty($query_string) || $query_string == 'index')) echo " id=\"active\"";?>><a href="index.php"<?php if($currentFile == 'index.php') echo " id=\"current\" name=\"current\"";?>>Welcome!</a></li>
		<li<?php if(($currentFile == 'index.php' && $query_string == 'select') || $first_four_chars == 'draf') echo " id=\"active\"";?>><a href="index.php?q=select"<?php if($first_four_chars == "draf") echo " id=\"current\" name=\"current\"";?>>Draft Central</a></li>
			<?php  if(!isLoggedIn()) {//if the user is not authenticated?>
			<li<?php if($currentFile == 'login.php') echo " id=\"active\"";?>><a href="login.php"<?php if($currentFile == 'login.php') echo " id=\"current\" name=\"current\"";?>>Commissioner Login</a></li>
	<?php
		}else {?><li<?php if($first_four_chars == 'ccp.' || $first_four_chars == "comm") echo " id=\"active\"";?>><a href="control_panel.php?action=home"<?php if($first_four_chars == 'ccp.' || $first_four_chars == "comm") echo " id=\"current\" name=\"current\"";?>>Control Panel</a></li>
		<li<?php if($currentFile == 'logout.php') echo " id=\"active\"";?>><a href="logout.php"<?php if($currentFile == 'logout.php') echo " id=\"current\" name=\"current\"";?>>Log Out</a></li>
	<?php   }
		}   ?>
	</ul>
	</div>
</div>