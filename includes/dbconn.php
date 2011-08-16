<?php
/*
	This document implements functions necessary to connect to the MySQL server
	and also to select a database within that server so that we may operate on it.
 * 
 * NOTE: You only have to define the username & password once below. For the time being, these will be global (BAD!)
*/
$username = "your_username";
$password = "your_password";
$host = "localhost";
$database = "phpdraft";

class php_draft_connect {

	public static function set_conn() {//Create a connection to MySQL with proper authentication so we can select a DB
		/*$username = "your_username";
		$password = "your_password";*/
		global $username;
		global $password;
		global $host;
		global $database;
		
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
	
	/**
	 * NOTE: 
	 */
	public static function setupPDOHandle() {
		/*$database_username = "phpdraft";
		$database_password = "mypass";
		$database_host = "localhost";
		$database_name = "phpdraft";*/
		
		global $username;
		global $password;
		global $host;
		global $database;
		
		try {
			$dbh = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
		}catch(PDOException $e) {
			echo "Error: " . $e->getMessage();
			die();
		}
		
		return $dbh;
	}
}
?>