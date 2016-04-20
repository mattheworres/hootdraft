<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

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

$app['phpdraft.TradeValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\TradeValidator($app);
};

$app['phpdraft.PickValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\PickValidator($app);
};

$app['phpdraft.ProPlayerValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\ProPlayerValidator($app);
};

$app['phpdraft.DepthChartPositionValidator'] = function () use ($app) {
  return new \PhpDraft\Domain\Validators\DepthChartPositionValidator($app);
};

$app['phpdraft.ObjectCache'] = function () use ($app) {
  return new \phpFastCache();
};