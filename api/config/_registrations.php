<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//Services
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
  return new \PhPDraft\Domain\Services\DraftService($app);
};

//Repositories
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

//Validators
$app['phpdraft.LoginUserValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\LoginUserValidator($app);
};

$app['phpdraft.DraftValidator'] = function() use ($app) {
  return new \PhpDraft\Domain\Validators\DraftValidator($app);
};