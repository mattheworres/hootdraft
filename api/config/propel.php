<?php

// if (!$app instanceof Silex\Application) {
//   throw new Exception('Invalid application setup.');
// }

//TODO: Figure out if a service can be used if we need to re-use this pattern anywhere else:
// $configuration_variables = array('DB_USER', 'DB_PASS');

// foreach($configuration_variables as $variable) {
//     define($variable, get_cfg_var("phpdraft.cfg.$variable"));
// }

//TODO: Add easier support for dynamic DSN changing depending on adapter choice.
$propel_adapter = 'mysql';
$server = 'localhost';
$database_name = 'phpdraft';

//CAUTION: DO NOT EDIT USERNAME/PWD BELOW! See README.md for directions on what to set in your php.ini file
return [
    'propel' => [
        'database' => [
            'connections' => [
                'phpdraft' => [
                    'adapter'    => $propel_adapter,
                    'classname'  => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
                    'dsn'        => "$propel_adapter:host=$server;dbname=$database_name",
                    'user'       => DB_USER,
                    'password'   => DB_PASS,
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