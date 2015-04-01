<?php

namespace Phpdraft\Controllers\Providers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class Index implements ControllerProviderInterface {
  public function connect(Application $app)
  {
    $index = $app['controllers_factory'];

    $index->get('/', '\\PhpDraft\\Controllers\\IndexController::Index');

    return $index;
  }
}