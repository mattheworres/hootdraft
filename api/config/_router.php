<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//$app->mount('/index', new \PhpDraft\Controllers\Providers\Index());

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/* Index */
$app['index.controller'] = $app->share(function() use ($app) {
  return new PhpDraft\Controllers\IndexController();
});

$app->get('/', "index.controller:Index");