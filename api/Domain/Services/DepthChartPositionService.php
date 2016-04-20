<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PhpDraftResponse;

class DraftService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

}