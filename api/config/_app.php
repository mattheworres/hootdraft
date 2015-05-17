<?php 

require_once __DIR__.'/../../vendor/autoload.php';

$app = new Silex\Application();

$configuration_variables = array('DB_USER', 'DB_PASS', 'AUTH_KEY');

foreach($configuration_variables as $variable) {
  define($variable, get_cfg_var("phpdraft.cfg.$variable")); 
}

require_once __DIR__.'/_settings.php';      //Handles settings users can define
require_once __DIR__.'/_database.php';      //Sets up database connections
require_once __DIR__.'/_log.php';           //Sets up logging
require_once __DIR__.'/_registrations.php'; //Sets up service, validator and repository dependency registration
require_once __DIR__.'/_router.php';        //Sets up controller routing
require_once __DIR__.'/_security.php';      //Sets up Symfony-based security & user authentication

return $app;