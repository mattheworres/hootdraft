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

$app['commish.controller'] = function() {
  return new PhpDraft\Controllers\CommishController();
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

$app['roundtime.controller'] = function() {
  return new PhpDraft\Controllers\RoundTimeController();
};

$app['admin.index.controller'] = function() {
  return new PhpDraft\Controllers\Admin\IndexController();
};

$app['admin.draftstats.controller'] = function() {
  return new PhpDraft\Controllers\Admin\DraftStatsController();
};

$app['admin.proplayers.controller'] = function() {
  return new PhpDraft\Controllers\Admin\ProPlayerController();
};

$app['admin.users.controller'] = function() {
  return new PhpDraft\Controllers\Admin\UserController();
};

$app['commish.index.controller'] = function() {
  return new PhpDraft\Controllers\Commish\IndexController();
};

$app['commish.profile.controller'] = function() {
  return new PhpDraft\Controllers\Commish\UserProfileController();
};

$app['commish.draft.controller'] = function() {
  return new PhpDraft\Controllers\Commish\DraftController();
};

$app['commish.manager.controller'] = function() {
  return new PhpDraft\Controllers\Commish\ManagerController();
};

$app['commish.proplayer.controller'] = function() {
  return new PhpDraft\Controllers\Commish\ProPlayerController();
};

$app['commish.trade.controller'] = function() {
  return new PhpDraft\Controllers\Commish\TradeController();
};

$app['commish.pick.controller'] = function() {
  return new PhpDraft\Controllers\Commish\PickController();
};

$app->post('/login', 'authentication.controller:Login');
$app->post('/register', 'authentication.controller:Register');
$app->post('/verify', 'authentication.controller:VerifyAccount');
$app->post('/lostPassword', 'authentication.controller:LostPassword');
$app->get('/verifyToken', 'authentication.controller:VerifyResetPasswordToken');
$app->post('/resetPassword', 'authentication.controller:ResetPassword');

$app->get('/drafts', 'draft.controller:GetAll');
$app->get('/drafts/{commish_id}', 'draft.controller:GetAllByCommish');
$app->get('/draft/{id}', 'draft.controller:Get');
$app->get('/draft/{draft_id}/stats', 'draft.controller:GetStats')->before($draftViewable);

$app->get('/commissioners/search', 'commish.controller:SearchPublicCommissioners');
$app->get('/commissioners/{commish_id}', 'commish.controller:GetPublicCommissioner');

$app->get('/draft/{draft_id}/managers', 'manager.controller:GetAll')->before($draftViewable);

$app->get('/draft/{draft_id}/picks', 'pick.controller:GetAll')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/updated', 'pick.controller:GetUpdated')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/last', 'pick.controller:GetLast')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/next', 'pick.controller:GetNext')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/all', 'pick.controller:GetAllManagerPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/selected', 'pick.controller:GetSelectedManagerPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/round/{draft_round}/picks/all', 'pick.controller:GetAllRoundPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/round/{draft_round}/picks/selected', 'pick.controller:GetSelectedRoundPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/search', 'pick.controller:SearchPicks')->before($draftViewable)->before($draftInProgressOrCompleted);

$app->get('/draft/{draft_id}/trades', 'trade.controller:GetAll')->before($draftViewable)->before($draftInProgressOrCompleted);

$app->get('/draft/{draft_id}/timer/remaining', 'roundtime.controller:GetTimeRemaining')->before($draftViewable)->before($draftInProgress);

$app->get('/style', "index.controller:Style");

$app->post('/admin/draft/{draft_id}/stats', "admin.draftstats.controller:Create");
$app->post('/admin/proplayers', "admin.proplayers.controller:Upload");
$app->get('/admin/users', "admin.users.controller:Get");
$app->put('/admin/user/{user_id}', "admin.users.controller:Update");
$app->delete('/admin/user/{user_id}', "admin.users.controller:Delete");

$app->get('/commish', "commish.index.controller:Index");
$app->get('/commish/profile', "commish.profile.controller:Get");
$app->put('/commish/profile', "commish.profile.controller:Put");

$app->get('/commish/draft/create', "commish.draft.controller:GetCreate"); //Only requires commish role, handled by firewall
$app->get('/commish/draft/{draft_id}', "commish.draft.controller:Get")->before($commishEditableDraft);
$app->get('/commish/draft/{draft_id}/timers', "commish.draft.controller:GetTimers")->before($commishEditableDraft);
$app->post('/commish/draft', "commish.draft.controller:Create"); //Only requires commish role, handled by firewall
$app->put('/commish/draft/{draft_id}', "commish.draft.controller:Update")->before($commishEditableDraft)->before($draftSettingUp);
$app->put('/commish/draft/{draft_id}/status', "commish.draft.controller:UpdateStatus")->before($commishEditableDraft);
$app->delete('/commish/draft/{draft_id}', "commish.draft.controller:Delete")->before($commishEditableDraft);
$app->post('/commish/draft/{draft_id}/timers', "commish.draft.controller:SetTimers")->before($commishEditableDraft)->before($draftSettingUp);

$app->get('/commish/draft/{draft_id}/managers', "commish.manager.controller:Get")->before($commishEditableDraft);
$app->post('/commish/draft/{draft_id}/manager', "commish.manager.controller:Create")->before($commishEditableDraft)->before($draftSettingUp);
$app->post('/commish/draft/{draft_id}/managers', "commish.manager.controller:CreateMany")->before($commishEditableDraft)->before($draftSettingUp);
$app->put('/commish/draft/{draft_id}/managers/reorder', "commish.manager.controller:Reorder")->before($commishEditableDraft)->before($draftSettingUp);
$app->put('/commish/draft/{draft_id}/manager/{manager_id}', "commish.manager.controller:Update")->before($commishEditableDraft)->before($draftSettingUp);
$app->delete('/commish/draft/{draft_id}/manager/{manager_id}', "commish.manager.controller:Delete")->before($commishEditableDraft)->before($draftSettingUp);

$app->get('/commish/proplayers/search', "commish.proplayer.controller:Search"); //Only requires commish role, handled by firewall

$app->get('/commish/draft/{draft_id}/manager/{manager_id}/assets', "commish.trade.controller:GetAssets")->before($commishEditableDraft)->before($draftInProgress);
$app->post('/commish/draft/{draft_id}/trade', "commish.trade.controller:Create")->before($commishEditableDraft)->before($draftInProgress);

$app->get('/commish/draft/{draft_id}/pick/current', "commish.pick.controller:GetCurrent")->before($commishEditableDraft)->before($draftInProgress);
$app->post('/commish/draft/{draft_id}/pick/{pick_id}', "commish.pick.controller:Add")->before($commishEditableDraft)->before($draftInProgress);
$app->get('/commish/draft/{draft_id}/picks/lastFive', "commish.pick.controller:GetLast5")->before($commishEditableDraft)->before($draftInProgress);
$app->get('/commish/draft/{draft_id}/round/{draft_round}/picks', "commish.pick.controller:GetByRound")->before($commishEditableDraft)->before($draftInProgress);
$app->put('/commish/draft/{draft_id}/pick/{pick_id}', "commish.pick.controller:Update")->before($commishEditableDraft)->before($draftInProgress);
$app->get('/commish/draft/{draft_id}/picks/alreadyDrafted', "commish.pick.controller:AlreadyDrafted")->before($commishEditableDraft)->before($draftInProgress);

