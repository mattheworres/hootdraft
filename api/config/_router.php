<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app['authentication.controller'] = function() {
  return new PhpDraft\Controllers\AuthenticationController();
};

$app['index.controller'] = function() {
  return new PhpDraft\Controllers\IndexController();
};

$app['admin.index.controller'] = function() {
  return new PhpDraft\Controllers\Admin\IndexController();
};

$app->post('/login', 'authentication.controller:Login');
$app->get('/blart', 'authentication.controller:Blart');

$app->get('/', "index.controller:Index");

$app->get('/admin', "admin.index.controller:Index");