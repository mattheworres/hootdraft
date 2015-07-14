<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }
  
  public function GetManagerAssets($manager_id) {
    $response = new PhpDraftResponse();

    try {
      $assets = $this->app['phpdraft.PickRepository']->LoadManagerPicks($manager_id, false);

      $response->success = true;
      $response->manager_id = $manager_id;
      $response->assets = $assets;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}