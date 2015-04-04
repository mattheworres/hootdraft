<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//$app['propel.config_file'] = __DIR__.'/propel.php';
//$app['propel.model_path'] = __DIR__.'/api/Domain/Entities';
$app->register(new Propel\Silex\PropelServiceProvider(), array(
    'propel.config_file' => __DIR__.'/propel.php',
    'propel.model_path' => __DIR__.'/api/Domain/Entities'
  ));