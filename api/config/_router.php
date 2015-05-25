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

$app['draft.controller'] = function() {
  return new PhpDraft\Controllers\DraftController();
};

$app['manager.controller'] = function() {
  return new PhpDraft\Controllers\ManagerController();
};

$app['admin.index.controller'] = function() {
  return new PhpDraft\Controllers\Admin\IndexController();
};

$app['commish.index.controller'] = function() {
  return new PhpDraft\Controllers\Commish\IndexController();
};

$app['commish.profile.controller'] = function() {
  return new PhpDraft\Controllers\Commish\UserProfileController();
};

$app->post('/login', 'authentication.controller:Login');
$app->post('/register', 'authentication.controller:Register');
$app->post('/verify', 'authentication.controller:VerifyAccount');
$app->post('/lostPassword', 'authentication.controller:LostPassword');
$app->post('/resetPassword', 'authentication.controller:ResetPassword');

$app->get('/drafts', 'draft.controller:GetAll');
$app->get('/draft/{id}', 'draft.controller:Get');
$app->get('/drafts/{commissionerId}', 'draft.controller:GetAllByCommish');

$app->get('/draft/{draft_id}/managers', 'manager.controller:GetAll');

$app->get('/', "index.controller:Index");

$app->get('/admin', "admin.index.controller:Index");

$app->get('/commish', "commish.index.controller:Index");
$app->get('/commish/profile', "commish.profile.controller:Get");
$app->put('/commish/profile', "commish.profile.controller:Put");