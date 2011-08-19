<?php
require('includes/php_draft_class.php');
/**
 * This is the main settings file for PHP Draft. There are five lines that you need to edit in order to get started,
 * the server hostname that the database exists on, the name of the database on that server, the username & password
 * for the user that can read/write to that database, and the timezone that PHPDraft will exist on. Do not edit any lines
 * below that, unless you know what you're doing!
 */

$PHPD = new PHPDRAFT();

$PHPD->setDatabaseHostname	("localhost");
$PHPD->setDatabaseName		("phpdraft");
$PHPD->setDatabaseUsername	("your_username");
$PHPD->setDatabasePassword	("your_password");
$PHPD->setLocalTimezone(PHPDRAFT::TIMEZONE_EST);

/*************************************************
 ******** DO NOT EDIT BELOW THIS LINE ************
 *************************************************/

$DBH = $PHPD->setupPDOHandle();

session_start();

//Require the draft and user objects used on every page
require('models/draft_object.php');
require('models/user_object.php');

//Global-level commissioner object - used for printing commissioner's name publicly
$COMMISH = new user_object();
$COMMISH->getDefaultCommissioner(99999);

//Global-level logged in user... possibly the commish?
$LOGGED_IN_USER = new user_object();
$LOGGED_IN_USER->getCurrentlyLoggedInUser();
?>