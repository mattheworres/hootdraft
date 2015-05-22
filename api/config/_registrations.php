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

//Repositories
$app['phpdraft.LoginUserRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\LoginUserRepository($app);
};

$app['phpdraft.DraftRepository'] = function () use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftRepository($app);
};

//Validators
$app['phpdraft.LoginUserValidator'] = function () use($app) {
  return new \PhpDraft\Domain\Validators\LoginUserValidator($app);
};