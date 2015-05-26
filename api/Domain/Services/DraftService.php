<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DraftService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetCurrentPick($draft_id) {
    $draft_id = (int)$draft_id;

    $draft = $this->app['phpdraft.DraftRepository']->Load($draft_id);

    return (int)$draft->draft_current_pick;
  }
}