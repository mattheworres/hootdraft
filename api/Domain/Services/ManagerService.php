<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function CreateNewManager(Manager $manager) {
    $response = new PhpDraftResponse();

    try {
      $manager = $this->app['phpdraft.ManagerRepository']->Create($manager);

      $response->success = true;
      $response->manager = $manager;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function CreateManyManagers($draft_id, $managersArray) {
    $response = new PhpDraftResponse();

    try {
      $managers = $this->app['phpdraft.ManagerRepository']->CreateMany($draft_id, $managersArray);

      $response->success = true;
      $response->managers = $managers;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function ReorderManagers($managersIdArray) {
    $response = new PhpDraftResponse();

    try {
      $managers = $this->app['phpdraft.ManagerRepository']->ReorderManagers($managersIdArray);

      $response->success = true;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  /*public function UpdateManager(Draft $draft) {
    //TODO: Implement
    $response = new PhpDraftResponse();

    try {
      $draft = $this->app['phpdraft.DraftRepository']->Update($draft);

      $response->success = true;
      $response->draft = $draft;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }*/

  /*public function DeleteManager(Draft $draft) {
    //TODO: Implement
    $response = new PhpDraftResponse();

    try {
      //Delete all trades
      $this->app['phpdraft.TradeRepository']->DeleteAllTrades($draft->draft_id);
      //Delete all picks
      $this->app['phpdraft.PickRepository']->DeleteAllPicks($draft->draft_id);
      //Delete the draft
      $this->app['phpdraft.DraftRepository']->DeleteDraft($draft->draft_id);

      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }*/
}