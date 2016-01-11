<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsManagerValidForCreate($draft_id, Manager $manager) {
    $valid = true;
    $errors = array();

    try {
      $current_manager_count = $this->app['phpdraft.ManagerRepository']->GetNumberOfCurrentManagers($draft_id);
    } catch(\Exception $e) {
      $errors[] = $e->getMessage();
      $valid = false;
      $current_manager_count = 0;
    }
    
    if(empty($manager->draft_id)
      || empty($manager->manager_name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if(!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
      $errors[] = "Manager name is above maximum length.";
      $valid = false;
    }

    if($current_manager_count > 19) {
      $errors[] = "Unable to add more managers - 20 is the maximum number of managers allowed.";
      $valid = false;
    }

    if(!$this->app['phpdraft.ManagerRepository']->NameIsUnique($manager->manager_name)) {
      $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function AreManagersValidForCreate($draft_id, $managersArray) {
    $valid = true;
    $errors = array();
    $current_manager_count = $this->app['phpdraft.ManagerRepository']->GetNumberOfCurrentManagers($draft_id);
    $numberOfNewManagers = count($managersArray);
    $maxNumberOfManagers = 20 - $current_manager_count;
    $numberOfManagersOver = $numberOfNewManagers-$maxNumberOfManagers;
    $uniqueManagersCount = count(array_unique($managersArray));

    if($numberOfNewManagers > $maxNumberOfManagers) {
      $manager_noun = $numberOfManagersOver > 1 ? "managers" : "manager";
      $errors[] = "Unable to add $numberOfNewManagers new managers - the maximum number of managers is 20. Draft currently has $current_manager_count, so please remove $numberOfManagersOver $manager_noun to continue.";
      $valid = false;
    }

    if($numberOfNewManagers != $uniqueManagersCount) {
      $errors[] = "Two or more managers had the same name - please ensure all managers have unique names.";
      $valid = false;
    }

    $manager_number = 1;
    foreach($managersArray as $manager) {
      if(empty($manager->draft_id)
        || empty($manager->manager_name)) {
        $errors[] = "One or more missing fields for manager #$manager_number.";
        $valid = false;
      }

      if(!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
        $errors[] = "Manager #$manager_number's name is above the maximum length.";
        $valid = false;
      }

      if(!$this->app['phpdraft.ManagerRepository']->NameIsUnique($manager->manager_name, $draft_id)) {
        $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
        $valid = false;
      }

      $manager_number++;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function AreManagerIdsValidForOrdering($draft_id, $managersIdArray) {
    $valid = true;
    $errors = array();
    $current_manager_count = $this->app['phpdraft.ManagerRepository']->GetNumberOfCurrentManagers($draft_id);
    $ids_given = count($managersIdArray);

    if(count($managersIdArray) == 0) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if($ids_given > 20) {
      $errors[] = "Too many managers given - maximum number of managers is 20";
      $valid = false;
    }

    if($ids_given != $current_manager_count) {
      $manager_noun = $current_manager_count > 1 ? "managers" : "manager";
      $errors[] = "Incorrect number of managers given. Draft #$draft_id has $current_manager_count $manager_noun, but $ids_given were received.";
      $valid = false;
    }

    //Save ourself a little processing if we've been handled a faulty request:
    if($valid = true) {
      foreach($managersIdArray as $manager_id) {
        if(!$this->app['phpdraft.ManagerRepository']->ManagerExists($manager_id, $draft_id)) {
          $errors[] = "Manager #$manager_id does not exist.";
          $valid = false;
        }
      }
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsManagerValidForUpdate(Draft $draft, Manager $manager) {
    $valid = true;
    $errors = array();
    
    if(empty($manager->manager_id)
      || empty($manager->draft_id)
      || empty($manager->manager_name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if($draft->draft_id != $manager->draft_id) {
      $errors[] = "Manager does not belong to draft #$draft_id";
      $valid = false;
    }

    if(!empty($manager->manager_name) && strlen($manager->manager_name) > 255) {
      $errors[] = "Manager name is above maximum length.";
      $valid = false;
    }

    if(!$this->app['phpdraft.ManagerRepository']->NameIsUnique($manager->manager_name, $draft->draft_id, $manager->manager_id)) {
      $errors[] = "Manager name '$manager->manager_name' already exists - please choose another name.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}