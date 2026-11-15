<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Validators\ManagerValidator;

class ManagerService {
  public function __construct(private ManagerRepository $managerRepository, private TransactionService $transactions) {
  }

  public function CreateNewManager(Manager $manager) {
    $response = new HootDraftResponse();

    try {
      $manager = $this->transactions->Run(function () use ($manager) {
        // The validator's count check ran before this request had the draft to itself, so check again under the lock
        $this->managerRepository->LockDraft($manager->draft_id);

        if ($this->managerRepository->GetNumberOfCurrentManagers($manager->draft_id) >= ManagerValidator::MAX_MANAGERS) {
          throw new \Exception(ManagerValidator::MAX_MANAGERS_MESSAGE);
        }

        return $this->managerRepository->Create($manager);
      });

      $response->success = true;
      $response->manager = $manager;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function CreateManyManagers($draft_id, $managersArray) {
    $response = new HootDraftResponse();

    try {
      $managers = $this->transactions->Run(function () use ($draft_id, $managersArray) {
        // As in CreateNewManager: re-check the limit now that nobody else can add managers to this draft
        $this->managerRepository->LockDraft($draft_id);

        $current_manager_count = $this->managerRepository->GetNumberOfCurrentManagers($draft_id);

        if ($current_manager_count + count($managersArray) > ManagerValidator::MAX_MANAGERS) {
          throw new \Exception("Unable to add " . count($managersArray) . " new managers - the maximum number of managers is " . ManagerValidator::MAX_MANAGERS . ". Draft currently has $current_manager_count.");
        }

        return $this->managerRepository->CreateMany($draft_id, $managersArray);
      });

      $response->success = true;
      $response->managers = $managers;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function ReorderManagers($managersIdArray) {
    $response = new HootDraftResponse();

    try {
      $this->transactions->Run(fn () => $this->managerRepository->ReorderManagers($managersIdArray));

      $response->success = true;
    } catch (\Exception $e) {
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

      $managers = $this->transactions->Run(function () use ($manager, $draft_id) {
        $this->managerRepository->LockDraft($draft_id);
        $this->managerRepository->DeleteManager($manager->manager_id);

        //When we delete a manager, re-order the rest to close the gap. This is part of the delete: if it fails, so does the delete.
        $managersIdArray = array();

        foreach ($this->managerRepository->GetManagersByDraftOrder($draft_id) as $remainingManager) {
          $managersIdArray[] = $remainingManager->manager_id;
        }

        $this->managerRepository->ReorderManagers($managersIdArray);

        return $this->managerRepository->GetManagersByDraftOrder($draft_id);
      });

      $response->managers = $managers;
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}
