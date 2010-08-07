<?php
include_once("login_fcns.php");
?><div id="header_wrapper">
    <div id="header">
	<div style="width:50%; float:left;">
	    <h1>PHP<font color="#FFDF8C">Draft</font></h1>
	    <h2>Commissioner <?php echo $owner['Name'];?></h2>
	</div>
	<div style="width:50%; float:right;">
	    <h3>Web-Based Fantasy Draft Software</h3>
	</div>
    </div>
    <div id="navcontainer">
	<ul id="navlist"><?php
	    $currentFile = $_SERVER["SCRIPT_NAME"];
	    $parts = Explode('/', $currentFile);
	    $currentFile = $parts[count($parts) - 1];
	    $first_four_chars = substr($currentFile, 0, 4);

	    if(!isLoggedIn()) {//if the user is not authenticated?>
            <li<?php if($currentFile == 'index.php') echo " id=\"active\"";?>><a href="index.php"<?php if($currentFile == 'index.php') echo " id=\"current\" name=\"current\"";?>>Welcome!</a></li>
	    <li<?php if($first_four_chars == 'draf') echo " id=\"active\"";?>><a href="draft_select.php"<?php if($first_four_chars == "draf") echo " id=\"current\" name=\"current\"";?>>Draft Central</a></li>
            <li<?php if($currentFile == 'login.php') echo " id=\"active\"";?>><a href="login.php"<?php if($currentFile == 'login.php') echo " id=\"current\" name=\"current\"";?>>Commissioner Login</a></li>
    <?php
	    }else {?><li<?php if($currentFile == 'index.php') echo " id=\"active\"";?>><a href="index.php"<?php if($currentFile == 'index.php') echo " id=\"current\" name=\"current\"";?>>Welcome!</a></li>
	    <li<?php if($first_four_chars == 'draf') echo " id=\"active\"";?>><a href="draft_select.php"<?php if($first_four_chars == "draf") echo " id=\"current\" name=\"current\"";?>>Draft Central</a></li>
            <li<?php if($first_four_chars == 'ccp.' || $first_four_chars == "comm") echo " id=\"active\"";?>><a href="ccp.php"<?php if($first_four_chars == 'ccp.' || $first_four_chars == "comm") echo " id=\"current\" name=\"current\"";?>>Control Panel</a></li>
	    <li<?php if($currentFile == 'logout.php') echo " id=\"active\"";?>><a href="logout.php"<?php if($currentFile == 'logout.php') echo " id=\"current\" name=\"current\"";?>>Log Out</a></li>
    <?php }?>
	</ul>
    </div>
</div>