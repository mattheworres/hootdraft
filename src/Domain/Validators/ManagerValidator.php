<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\ManagerRepository;

class ManagerValidator {
  const MAX_MANAGERS = 20;
  const MAX_MANAGERS_MESSAGE = "Unable to add more managers - 20 is the maximum number of managers allowed.";

  public function __construct(private ManagerRepository $managerRepository) {
  }

  public function IsManagerValidForCreate($draft_id, Manager $manager) {
    $valid = true;
    $errors = array();

    try {
      $current_manager_count = $this->managerRepository->GetNumberOfCurrentManagers($draft_id);
    } catch (\Exception $e) {
      $errors[] = $e->getMessage();
      $valid = false;
      $current_manager_count = 0;
    }

    if (empty($manager->draft_id)
      || empty($manager->manager_name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if (!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
      $errors[] = "Manager name is above maximum length.";
      $valid = false;
    }

    if ($current_manager_count >= self::MAX_MANAGERS) {
      $errors[] = self::MAX_MANAGERS_MESSAGE;
      $valid = false;
    }

    // The old call omitted $draft_id, which is a required argument (an ArgumentCountError since PHP 7.1)
    if (!$this->managerRepository->NameIsUnique((string) $manager->manager_name, $draft_id)) {
      $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function AreManagersValidForCreate($draft_id, $managersArray) {
    $valid = true;
    $errors = array();
    $current_manager_count = $this->managerRepository->GetNumberOfCurrentManagers($draft_id);
    $numberOfNewManagers = count($managersArray);
    $maxNumberOfManagers = self::MAX_MANAGERS - $current_manager_count;
    $numberOfManagersOver = $numberOfNewManagers - $maxNumberOfManagers;
    // Names are compared case-insensitively, like NameIsUnique does for the managers already saved
    $uniqueManagersCount = count(array_unique(array_map(fn($manager) => strtolower((string) $manager->manager_name), $managersArray)));

    if ($numberOfNewManagers > $maxNumberOfManagers) {
      $manager_noun = $numberOfManagersOver > 1 ? "managers" : "manager";
      $errors[] = "Unable to add $numberOfNewManagers new managers - the maximum number of managers is 20. Draft currently has $current_manager_count, so please remove $numberOfManagersOver $manager_noun to continue.";
      $valid = false;
    }

    if ($numberOfNewManagers != $uniqueManagersCount) {
      $errors[] = "Two or more managers had the same name - please ensure all managers have unique names.";
      $valid = false;
    }

    $manager_number = 1;
    foreach ($managersArray as $manager) {
      if (empty($manager->draft_id)
        || empty($manager->manager_name)) {
        $errors[] = "One or more missing fields for manager #$manager_number.";
        $valid = false;
      }

      if (!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
        $errors[] = "Manager #$manager_number's name is above the maximum length.";
        $valid = false;
      }

      if (!$this->managerRepository->NameIsUnique((string) $manager->manager_name, $draft_id)) {
        $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
        $valid = false;
      }

      $manager_number++;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function AreManagerIdsValidForOrdering($draft_id, $managersIdArray) {
    $valid = true;
    $errors = array();
    $current_manager_count = $this->managerRepository->GetNumberOfCurrentManagers($draft_id);
    $ids_given = count($managersIdArray);

    if (count($managersIdArray) == 0) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if ($ids_given > 20) {
      $errors[] = "Too many managers given - maximum number of managers is 20";
      $valid = false;
    }

    if ($ids_given != count(array_unique($managersIdArray))) {
      $errors[] = "Two or more of the managers given are the same manager.";
      $valid = false;
    }

    if ($ids_given != $current_manager_count) {
      $manager_noun = $current_manager_count > 1 ? "managers" : "manager";
      $errors[] = "Incorrect number of managers given. Draft #$draft_id has $current_manager_count $manager_noun, but $ids_given were received.";
      $valid = false;
    }

    //Save ourself a little processing if we've been handed a faulty request:
    if ($valid) {
      foreach ($managersIdArray as $manager_id) {
        if (!$this->managerRepository->ManagerExists($manager_id, $draft_id)) {
          $errors[] = "Manager #$manager_id does not exist.";
          $valid = false;
        }
      }
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsManagerValidForUpdate(Draft $draft, Manager $manager) {
    $valid = true;
    $errors = array();

    if (empty($manager->manager_id)
      || empty($manager->draft_id)
      || empty($manager->manager_name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if ($draft->draft_id != $manager->draft_id) {
      $errors[] = "Manager does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    if (!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
      $errors[] = "Manager name is above maximum length.";
      $valid = false;
    }

    if (!$this->managerRepository->NameIsUnique((string) $manager->manager_name, $draft->draft_id, $manager->manager_id)) {
      $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }
}
