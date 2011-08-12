<?php
/*
	This document implements functions necessary to connect to the MySQL server
	and also to select a database within that server so that we may operate on it.
*/
class php_draft_connect {

	public static function set_conn() {//Create a connection to MySQL with proper authentication so we can select a DB
		$username = "your_username";
		$password = "your_password";
		$connection = mysql_connect("localhost",$username,$password);

		if(!$connection) {
			die('Could not connect: ' . mysql_error());
		}
		
		php_draft_connect::select_db("phpdraft", $connection);
	}

	private static function select_db($dbname, $connection) {
		if(!$connection || !isset($connection)) {
			die('Could not connect: Connection variable not set!');
		}

		if(!isset($dbname)) {
			die('Could not select database: no database name given.');
		}

		if(!mysql_select_db($dbname, $connection)) {
			die('Could not select database: ' . mysql_error());
		}
	}
	
	
	public static function setupPDOHandle() {
		$database_username = "your_username";
		$database_password = "your_password";
		$database_host = "localhost";
		$database_name = "phpdraft";
		
		try {
			$dbh = new PDO('mysql:host=' . $database_host . ';dbname=' . $database_name, $database_username, $database_password);
		}catch(PDOException $e) {
			echo "Error: " . $e->getMessage();
			die();
		}
		
		return $dbh;
	}
}
?>