<?php

$configuration_variables = array('DB_USER', 'DB_PASS');

foreach($configuration_variables as $variable) {
    define($variable, get_cfg_var("phpdraft.cfg.$variable"));
}

$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('phpdraft', 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration(array (
  'classname' => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
  'dsn' => 'mysql:host=localhost;dbname=phpdraft',
  'user' => DB_USER,
  'password' => DB_PASS,
  'attributes' =>
  array (
    'ATTR_EMULATE_PREPARES' => false,
  ),
));
$manager->setName('phpdraft');
$serviceContainer->setConnectionManager('phpdraft', $manager);
$serviceContainer->setDefaultDatasource('phpdraft');