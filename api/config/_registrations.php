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

//Repositories
//Repositories are classes that are responsible for loading and saving data.
$app['phpdraft.LoginUserRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\LoginUserRepository($app);
};

$app['phpdraft.DraftRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftRepository($app);
};

$app['phpdraft.ManagerRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\ManagerRepository($app);
};

$app['phpdraft.PickRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\PickRepository($app);
};

$app['phpdraft.TradeRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\TradeRepository($app);
};

$app['phpdraft.DraftDataRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftDataRepository($app);
};

$app['phpdraft.RoundTimeRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\RoundTimeRepository($app);
};

//Validators
//Validators are for ensuring that request data is valid, and ensures save data
//does not result in corrupt data.
$app['phpdraft.LoginUserValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\LoginUserValidator($app);
};

$app['phpdraft.DraftValidator'] = function() use ($app) {
  return new \PhpDraft\Domain\Validators\DraftValidator($app);
};

$app['phpdraft.RoundTimeValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\RoundTimeValidator($app);
};

$app['phpdraft.ManagerValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\ManagerValidator($app);
};