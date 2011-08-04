<?php
// Global code include file - used to setup site in a consistent manner

//Require the DB connection file and start the connection
require('includes/dbconn.php');
set_conn();

//Start the PHP session - important this is done before any output is made
session_start();

//Set the application's timezone to EST
date_default_timezone_set('America/New_York');

//Require the draft object on every page - used everywhere
require('models/draft_object.php');

//Global-level commissioner object
require('models/user_object.php');
$COMMISH = new user_object();
$COMMISH->getDefaultCommissioner(99999);

//Global-level logged in user... possibly the commish?
$LOGGED_IN_USER = new user_object();
$LOGGED_IN_USER->getCurrentlyLoggedInUser();
?>