<?php
/*
	This document implements functions necessary to connect to the MySQL server
	and also to select a database within that server so that we may operate on it.
	
	To avoid multiple declarations of these functions, ALWAYS use include_once()
	when including the file.
*/
$my_conn;
$admin_password = "";
function set_conn() {//Create a connection to MySQL with proper authentication so we can select a DB
    global $my_conn;	//Make sure we're using a global var so other fcns can access it
    $username = "your_username";
    $password = "your_password";
    $my_conn = mysql_connect("localhost",$username,$password);
    //$my_conn = mysql_connect("localhost","root","");
    if(!$my_conn) {
	die('Could not connect: ' . mysql_error());
    }
    select_db("scsports_phpdraft");
}

function select_db($dbname) {
    global $my_conn;	//Use the global version

    if(!$my_conn || !isset($my_conn)) {
	die('Could not connect: Connection variable not set!');
    }

    if(!isset($dbname)) {//We weren't given a DB name
	die('Could not select database: no database name given.');
    }

    if(!mysql_select_db($dbname, $my_conn)) {//If we couldn't select that database
	die('Could not select database: ' . mysql_error());
    }
}
?>