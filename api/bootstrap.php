<?php

// try{
//     $dbh = new pdo( 'mysql:host=localhost;dbname=phpdraft',
//                     'root',
//                     'shadylady',
//                     array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
//     die(json_encode(array('outcome' => true)));
// }
// catch(PDOException $ex){
//     die(json_encode(array('outcome' => false, 'message' => 'Unable to connect')));
// }

$app = require_once __DIR__ . '/config/_app.php';

if ($app instanceof Silex\Application) {
    $app->run();
} else {
    echo 'Failed to initialize application.';
}