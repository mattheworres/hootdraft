<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

/**
 * @global string LOGFILE_NAME
 */

if($app['debug'] == true) {
  $app->register(new Silex\Provider\MonologServiceProvider(), array(
      'monolog.logfile' => __DIR__ . '/logs/' . LOGFILE_NAME,
  ));
}