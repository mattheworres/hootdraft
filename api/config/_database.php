<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => 'pdo_mysql',
        'host'      => $app['phpdraft.database_host'],
        'dbname'    => $app['phpdraft.database_name'],
        'user'      => '',//DB_USER,
        'password'  => '',//DB_PASS,
        //'unix_socket' => '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
        'charset' =>'utf8', 
    )
));