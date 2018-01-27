<?php 

require_once dirname(__FILE__) . '/../../vendor/autoload.php';

$app = new Silex\Application();

//Handles settings users can define
//Please see README.md for instructions on how to setup
require_once dirname(__FILE__) . '/../../appsettings.php';

$app['debug'] = DEBUG_MODE;

require_once dirname(__FILE__) . '/_database.php'; //Sets up database connections
require_once dirname(__FILE__) . '/_log.php'; //Sets up logging

//Registrations with Pimple DI
require_once dirname(__FILE__) . '/_registerServices.php';
require_once dirname(__FILE__) . '/_registerRepositories.php';
require_once dirname(__FILE__) . '/_registerValidators.php';
require_once dirname(__FILE__) . '/_registerFactories.php';

require_once dirname(__FILE__) . '/_middlewares.php'; //Defines middleware handlers for shared logic, like ensuring a draft is editable
require_once dirname(__FILE__) . '/_router.php'; //Sets up controller routing
require_once dirname(__FILE__) . '/_security.php'; //Sets up Symfony-based security & user authentication

return $app;