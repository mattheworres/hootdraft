<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DraftService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  
}