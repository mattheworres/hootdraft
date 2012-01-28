<?php
require("includes/global_setup.php");

DEFINE("ACTIVE_TAB", "HELP");
DEFINE("ACTION", isset($_REQUEST['action']) ? $_REQUEST['action'] : "");

switch(ACTION) {
	default:
		require("views/help/index.php");
		exit(0);
		break;
}
?>
