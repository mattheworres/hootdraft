<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//See api/config/propel.php for database settings

//Provide the base URL for the API:
$app['phpdraft.apiBaseUrl'] = 'http://localhost/phpdraft/api';

$app['phpdraft.database_name'] = 'phpdraft';

$app['phpdraft.database_host'] = 'localhost';

//Enable or disable autocomplete on pick entry screen; DEFAULT: true
$app['phpdraft.use_autocomplete'] = true;

//Enable or disable (true/false) PHP timeout for performing large CSV uploads; DEFAULT: false
$app['phpdraft.set_csv_timeout'] = false;

//Use extended NFL rosters and positions (defensive players); DEFAULT: false
//TODO: Move to a setting per-draft and not app-wide
$app['phpdraft.use_nfl_extended'] = false;

//Set Silex into debug mode; DEFAULT: false
$app['debug'] = true;