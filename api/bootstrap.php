<?php

$app = require_once __DIR__ . '/config/_app.php';

if ($app instanceof Silex\Application) {
    $app->run();
} else {
    echo 'Failed to initialize application.';
}