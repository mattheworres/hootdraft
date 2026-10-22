<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Validators\ManagerValidator;

class ManagerService {
  public function __construct(private ManagerRepository $managerRepository, private \PDO $pdo) {
  }

  public function CreateNewManager(Manager $manager) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      // The validator's count check ran before this request had the draft to itself, so check again under the lock
      $this->managerRepository->LockDraft($manager->draft_id);

      if ($this->managerRepository->GetNumberOfCurrentManagers($manager->draft_id) >= ManagerValidator::MAX_MANAGERS) {
        $this->rollBack();

        return new HootDraftResponse(false, array(ManagerValidator::MAX_MANAGERS_MESSAGE));
      }

      $manager = $this->managerRepository->Create($manager);

      $this->pdo->commit();

      $response->success = true;
      $response->manager = $manager;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function CreateManyManagers($draft_id, $managersArray) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      // As in CreateNewManager: re-check the limit now that nobody else can add managers to this draft
      $this->managerRepository->LockDraft($draft_id);

      $current_manager_count = $this->managerRepository->GetNumberOfCurrentManagers($draft_id);

      if ($current_manager_count + count($managersArray) > ManagerValidator::MAX_MANAGERS) {
        $this->rollBack();

        return new HootDraftResponse(false, array("Unable to add " . count($managersArray) . " new managers - the maximum number of managers is " . ManagerValidator::MAX_MANAGERS . ". Draft currently has $current_manager_count."));
      }

      $managers = $this->managerRepository->CreateMany($draft_id, $managersArray);

      $this->pdo->commit();

      $response->success = true;
      $response->managers = $managers;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function ReorderManagers($managersIdArray) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      $this->managerRepository->ReorderManagers($managersIdArray);

      $this->pdo->commit();

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateManager(Manager $manager) {
    $response = new HootDraftResponse();

    try {
      $manager = $this->managerRepository->Update($manager);

      $response->success = true;
      $response->manager = $manager;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteManager(Manager $manager) {
    $response = new HootDraftResponse();

    try {
      $draft_id = $manager->draft_id;

      $this->pdo->beginTransaction();
      $this->managerRepository->LockDraft($draft_id);
      $this->managerRepository->DeleteManager($manager->manager_id);

      //When we delete a manager, re-order the rest to close the gap. This is part of the delete: if it fails, so does the delete.
      $managersIdArray = array();

      foreach ($this->managerRepository->GetManagersByDraftOrder($draft_id) as $remainingManager) {
        $managersIdArray[] = $remainingManager->manager_id;
      }

      $this->managerRepository->ReorderManagers($managersIdArray);

      $managers = $this->managerRepository->GetManagersByDraftOrder($draft_id);

      $this->pdo->commit();

      $response->managers = $managers;
      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  private function rollBack() {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }
  }
}
