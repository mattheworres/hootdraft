	<?php
	include_once("dbconn.php");
		include_once("models/user_object.php");
		
	set_conn();
	
	date_default_timezone_set('America/New_York');

		$owner = new user_object();
		$owner->getDefaultCommissioner(99999);
	?><meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />
	<title><?php  echo $owner->public_name; ?>'s PHPDraft | Web-Based Fantasy Draft Software</title>
	<link href="css/style.css" type="text/css" rel="stylesheet" />
	<link href="css/jquery-ui-1.8.14.custom.css" type="text/css" rel="stylesheet" />