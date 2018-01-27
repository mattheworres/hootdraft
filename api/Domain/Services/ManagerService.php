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
    } catch (\Exception $e) {
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
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function ReorderManagers($managersIdArray) {
    $response = new PhpDraftResponse();

    try {
      $this->app['phpdraft.ManagerRepository']->ReorderManagers($managersIdArray);
      
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateManager(Manager $manager) {
    $response = new PhpDraftResponse();

    try {
      $manager = $this->app['phpdraft.ManagerRepository']->Update($manager);

      $response->success = true;
      $response->manager = $manager;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteManager(Manager $manager) {
    $response = new PhpDraftResponse();

    try {
      $draft_id = $manager->draft_id;
      $this->app['phpdraft.ManagerRepository']->DeleteManager($manager->manager_id);

      //When we delete a manager, re-order them to exclude deleted manager:
      $managers = $this->app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft_id);
      $managersIdArray = array();

      foreach ($managers as $manager) {
        $managersIdArray[] = $manager->manager_id;
      }

      $this->ReorderManagers($managersIdArray);

      $managers = $this->app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft_id);

      $response->managers = $managers;
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}