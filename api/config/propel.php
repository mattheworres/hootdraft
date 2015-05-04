<?php

/* Uncomment this section when using the propel commandline generator:

$propel_adapter = 'mysql';
$server = '127.0.0.1:3306';
$database_name = 'phpdraft';

return [
    'propel' => [
        'database' => [
            'connections' => [
                'phpdraft' => [
                    'adapter'    => $propel_adapter,
                    'classname'  => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
                    'dsn'        => "$propel_adapter:host=$server;dbname=$database_name",
                    'user'       => DB_USER,
                    'password'   => '',
                    'attributes' => []
                ]
            ]
        ],
        'runtime' => [
            'defaultConnection' => 'phpdraft',
            'connections' => ['phpdraft']
        ],
        'generator' => [
            'defaultConnection' => 'phpdraft',
            'connections' => ['phpdraft']
        ]
    ]
];
/**/

$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('phpdraft', 'mysql');

$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration(array (
  'classname' => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
  'dsn' => 'mysql:host=localhost;dbname=phpdraft',
  //'user' => DB_USER,
  //'password' => DB_PASS,
  'attributes' =>
  array (
    'ATTR_EMULATE_PREPARES' => false,
  ),
));
$manager->setName('phpdraft');

$serviceContainer->setConnectionManager('phpdraft', $manager);
$serviceContainer->setDefaultDatasource('phpdraft');
/**/