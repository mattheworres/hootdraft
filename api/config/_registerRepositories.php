<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

//Repositories
//Repositories are classes that are responsible for loading and saving data.
$app['phpdraft.LoginUserRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\LoginUserRepository($app);
};

$app['phpdraft.DraftRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftRepository($app);
};

$app['phpdraft.ManagerRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\ManagerRepository($app);
};

$app['phpdraft.PickRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\PickRepository($app);
};

$app['phpdraft.TradeRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\TradeRepository($app);
};

$app['phpdraft.DraftDataRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftDataRepository($app);
};

$app['phpdraft.RoundTimeRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\RoundTimeRepository($app);
};

$app['phpdraft.ProPlayerRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\ProPlayerRepository($app);
};

$app['phpdraft.DraftStatsRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\DraftStatsRepository($app);
};

$app['phpdraft.DepthChartPositionRepository'] = function() use ($app) {
  return new \PhpDraft\Domain\Repositories\DepthChartPositionRepository($app);
};