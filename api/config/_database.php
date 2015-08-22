<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => DB_DRIVER,
        'host'      => DB_HOST,
        'dbname'    => DB_NAME,
        'port'      => DB_PORT,
        'user'      => DB_USER,
        'password'  => DB_PASS,
        //'unix_socket' => '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
        'charset' =>'utf8', 
    )
));