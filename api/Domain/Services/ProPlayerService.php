<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ProPlayerService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }
  
  public function SearchPlayers($league, $first, $last, $team, $position) {
    $response = new PhpDraftResponse();

    try {
      $players = $this->app['phpdraft.ProPlayerRepository']->SearchPlayers($league, $first, $last, $team, $position);

      $response->success = true;
      $response->players = $players;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}