<?php
require('autoloader.php');
require('includes/php_draft_class.php');
/**
 * This is the main settings file for PHP Draft. There are five lines that you need to edit in order to get started,
 * the server hostname that the database exists on, the name of the database on that server, the username & password
 * for the user that can read/write to that database, and the timezone that PHPDraft will exist on. Do not edit any lines
 * below that, unless you know what you're doing!
 */
$PHPD = new PHPDRAFT();

/** This is usually unchanged - the server name or address the database is hosted on */
$PHPD->setDatabaseHostname("localhost");

/** The name of the PHPDraft database */
$PHPD->setDatabaseName("phpdraft");

/** The username that has rights to the PHPDraft database */
$PHPD->setDatabaseUsername("YOUR_USERNAME");

/** The password for the username above */
$PHPD->setDatabasePassword("YOUR_PASSWORD");

/** The timezone PHPDraft will be operating in. PHPDRAFT class has other constants available. */
$PHPD->setLocalTimezone(PHPDRAFT::TIMEZONE_EST);

/** Enable or disable autocomplete on the pick entry screen */
$PHPD->setUseAutocomplete(true);

/** Enable (true) or disable (false) PHP timeout for performing large CSV uploads. */
$PHPD->setCsvTimeout(false);

/** Use extended NFL rosters and positions (defensive players) - false by default */
$PHPD->setUseNFLExtended(false);

/* * ***********************************************
 * ******* DO NOT EDIT BELOW THIS LINE ************
 * *********************************************** */

$DBH = $PHPD->setupPDOHandle();

session_start();

//Global-level commissioner object - used for printing commissioner's name publicly
$COMMISH = new user_object();
$COMMISH->getDefaultCommissioner(99999);

//Global-level logged in user... possibly the commish?
$LOGGED_IN_USER = new user_object();
$LOGGED_IN_USER->getCurrentlyLoggedInUser();
?>