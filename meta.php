	<?php
	include_once("dbconn.php");
	set_conn();
	
	date_default_timezone_set('America/New_York');
	$owner = mysql_fetch_array(mysql_query("SELECT Name FROM user_login WHERE UserID = '99999' LIMIT 1"));
	?><meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />
    <title><?php echo $owner['Name'];?>'s PHPDraft | Web-Based Fantasy Draft Software</title>
    <link href="css/style.css" type="text/css" rel="stylesheet" />