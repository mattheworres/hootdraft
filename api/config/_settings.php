<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//See api/config/propel.php for database settings

//Provide the base URL for the Angular app (no trailing slash):
$app['phpdraft.appBaseUrl'] = 'http://localhost/phpdraft';

//Provide the base URL for the API (no trailing slash):
$app['phpdraft.apiBaseUrl'] = 'http://localhost/phpdraft/api';

//Provide the name of the database
$app['phpdraft.database_name'] = 'phpdraft';

//Provide the host of the database server
$app['phpdraft.database_host'] = 'localhost';

//Provide the host of the SMTP mail server
$app['phpdraft.smtp_server'] = 'localhost';

//Provide the port to access the SMTP mail server on
$app['phpdraft.smtp_port'] = 1025;

//Provide the number of seconds for authentication to be set before it needs refreshed (default: 1 day - 86,400)
$app['phpdraft.auth_seconds'] = 86400;

//Provide the name of the header to store the authorization token in. (Default: "X-Access-Token")
$app['phpdraft.auth_token'] = 'X-Access-Token';

//Provide the name of the header to store the draft password in (Default: "X-PhpDraft-DraftPassword")
$app['phpdraft.draft_password'] = 'X-PhpDraft-DraftPassword';

//Enable or disable autocomplete on pick entry screen; DEFAULT: true
$app['phpdraft.use_autocomplete'] = true;

//Enable or disable (true/false) PHP timeout for performing large CSV uploads; DEFAULT: false
$app['phpdraft.set_csv_timeout'] = false;

//Use extended NFL rosters and positions (defensive players); DEFAULT: false
//TODO: Move to a setting per-draft and not app-wide
$app['phpdraft.use_nfl_extended'] = false;

//Set Silex into debug mode; DEFAULT: false
$app['debug'] = false;