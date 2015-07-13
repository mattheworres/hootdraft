<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function Get(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $managers = $app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft->draft_id);

    return $app->json($managers, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $manager = new Manager();
    $manager->draft_id = $draft_id;
    $manager->manager_name = $request->get('manager_name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForCreate($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateNewManager($manager);
    $responseType = ($response->success ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function CreateMany(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $managersJson = $request->get('managers');
    $newManagers = array();

    foreach($managersJson as $managerRequest) {
      $newManager = new Manager();
      $newManager->draft_id = $draft_id;
      $newManager->manager_name = $managerRequest['manager_name'];

      $newManagers[] = $newManager;
    }

    $validity = $app['phpdraft.ManagerValidator']->AreManagersValidForCreate($draft, $newManagers);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateManyManagers($draft_id, $newManagers);
    $responseType = ($response->success ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function Reorder(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $managersIdJson = $request->get('ordered_manager_ids');
    $manager_ids = array();

    foreach($managersIdJson as $manager_id) {
      $manager_ids[] = (int)$manager_id;
    }

    $validity = $app['phpdraft.ManagerValidator']->AreManagerIdsValidForOrdering($draft_id, $manager_ids);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->ReorderManagers($manager_ids);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function Update(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $manager_id = $request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $manager->manager_name = $request->get('name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForUpdate($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->UpdateManager($manager);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function Delete(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $manager_id = $request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    if($manager->draft_id != $draft->draft_id) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST); 
    }

    $response = $app['phpdraft.ManagerService']->DeleteManager($manager);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }
}