<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//TODO: Validate that the rest of these are actually needed.

//Provide the name of the header to store the authorization token in. (Default: "X-Access-Token")
$app['phpdraft.auth_token'] = 'X-Access-Token';

//Provide the name of the header to store the draft password in (Default: "X-PhpDraft-DraftPassword")
$app['phpdraft.draft_password'] = 'X-PhpDraft-DraftPassword';

//Enable or disable (true/false) PHP timeout for performing large CSV uploads; DEFAULT: false
$app['phpdraft.set_csv_timeout'] = false;

//Set Silex into debug mode; DEFAULT: false
$app['debug'] = false;