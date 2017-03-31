<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//Services
//Services are classes that contain the vast majority of business logic.
//Controllers gather and organize request data and responses to requests,
//but hand all necessary data to a service for processing.
$app['phpdraft.SaltService'] = function() use ($app) {
  return new \PhpDraft\Config\Security\SaltService();
};

$app['phpdraft.EmailService'] = function() use ($app) {
  return new \PhpDraft\Domain\Services\EmailService($app);
};

$app['phpdraft.LoginUserService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\LoginUserService($app);
};

$app['phpdraft.DraftService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\DraftService($app);
};

$app['phpdraft.RoundTimeService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\RoundTimeService($app);
};

$app['phpdraft.ManagerService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\ManagerService($app);
};

$app['phpdraft.ProPlayerService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\ProPlayerService($app);
};

$app['phpdraft.TradeService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\TradeService($app);
};

$app['phpdraft.PickService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\PickService($app);
};

$app['phpdraft.UtilityService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\UtilityService($app);
};

$app['phpdraft.DepthChartPositionService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\DepthChartPositionService($app);
};

$app['phpdraft.DatabaseCacheService'] = function () use ($app) {
  return new \PhpDraft\Domain\Services\DatabaseCacheService($app);
};