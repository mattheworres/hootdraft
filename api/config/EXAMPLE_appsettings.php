<?php
/*
THIS IS ONLY A SAMPLE AND IS NOT MEANT TO CONTAIN APPLICATION SETTINGS.
VIEW README.MD FOR DETAILS ON WHERE TO PLACE THIS FILE
*/

//PHPDraft Application Settings

$configuration_variables = array(
  //Provide the DBAL driver name for the database (see http://silex.sensiolabs.org/doc/providers/doctrine.html)
  'DB_DRIVER' => 'pdo_mysql',
  'DB_HOST' => 'localhost', //on some *nix systems, it may be helpful to use '127.0.0.1' instead of 'localhost'
  //Name of your database
  'DB_NAME' => 'phpdraft',
  'DB_PORT' => 3306,
  'DB_USER' => 'database_username',
  'DB_PASS' => 'database_password',

  //Provide the number of seconds to cache certain objects (like drafts) in memory to save on database calls
  //Higher numbers make requests faster, but data can become stale more often as a result
  'CACHE_SECONDS' => 5,

  //Provide a secret key to generate all JWT authentication tokens. Make sure it's long, and keep it a secret!
  'AUTH_KEY' => 'auth',
  //Provide the number of seconds for tokens to be valid (after this time, they will need to re-authenticate; default: 86,400 (1 day))
  'AUTH_SECONDS' => 86400,
  //Grab your application's Google Recaptcha 2.0 secret key. Without it registration will not work!
  'RECAPTCHA_SECRET' => 'recaptcha',

  //Mail configuration
  'MAIL_SERVER' => 'localhost',
  'MAIL_USER' => 'mail_usr',
  'MAIL_PASS' => 'mail_pass',
  'MAIL_PORT' => '1025',

  //Provide the base URL for the Angular app (no trailing slash):
  'APP_BASE_URL' => 'http://www.yoursite.com',
  //Provide the base URL for the API (no trailing slash):
  'API_BASE_URL' => 'http://www.yoursite.com/api',
  //Provide the name of the header to store the authorization token in. (Default: "X-Access-Token")
  'AUTH_KEY_HEADER' => 'X-Access-Token',
  //Provide the name of the header to store the draft password in (Default: "X-PhpDraft-DraftPassword")
  'DRAFT_PASSWORD_HEADER' => 'X-PhpDraft-DraftPassword',
  //Name of the file you have created in /api/config/logs folder to contain Silex logging information
  'LOGFILE_NAME' => 'development.log',

  'SET_CSV_TIMEOUT' => false

);

foreach($configuration_variables as $name => $value) {
  define($name, $value);
}

unset($configuration_variables);
?>