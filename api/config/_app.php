<?php 

require_once __DIR__.'/../../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = false;

//Handles settings users can define
//Please see README for instructions on how to setup
require_once __DIR__.'/../../../appsettings.php';

require_once __DIR__.'/_database.php';      //Sets up database connections
require_once __DIR__.'/_log.php';           //Sets up logging
require_once __DIR__.'/_registrations.php'; //Sets up service, validator and repository dependency registration
require_once __DIR__.'/_middlewares.php';   //Defines middleware handlers for shared logic, like ensuring a draft is editable
require_once __DIR__.'/_router.php';        //Sets up controller routing
require_once __DIR__.'/_security.php';      //Sets up Symfony-based security & user authentication

return $app;