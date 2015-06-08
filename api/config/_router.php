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

$app['pick.controller'] = function() {
  return new PhpDraft\Controllers\PickController();
};

$app['trade.controller'] = function() {
  return new PhpDraft\Controllers\TradeController();
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

$app->get('/draft/{draft_id}/picks', 'pick.controller:GetAll');
$app->get('/draft/{draft_id}/picks/updated', 'pick.controller:GetUpdated');
$app->get('/draft/{draft_id}/picks/last', 'pick.controller:GetLast');
$app->get('/draft/{draft_id}/picks/next', 'pick.controller:GetNext');
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/all', 'pick.controller:GetAllManagerPicks');
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/selected', 'pick.controller:GetSelectedManagerPicks');
$app->get('/draft/{draft_id}/round/{draft_round}/picks/all', 'pick.controller:GetAllRoundPicks');
$app->get('/draft/{draft_id}/round/{draft_round}/picks/selected', 'pick.controller:GetSelectedRoundPicks');
$app->get('/draft/{draft_id}/picks/search', 'pick.controller:SearchPicks');

$app->get('/draft/{draft_id}/trades', 'trade.controller:GetAll');

$app->get('/', "index.controller:Index");

$app->get('/admin', "admin.index.controller:Index");

$app->get('/commish', "commish.index.controller:Index");
$app->get('/commish/profile', "commish.profile.controller:Get");
$app->put('/commish/profile', "commish.profile.controller:Put");