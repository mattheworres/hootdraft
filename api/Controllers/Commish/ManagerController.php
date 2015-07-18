<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function Get(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    $managers = $app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft_id);

    return $app->json($managers, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    $manager = new Manager();
    $manager->draft_id = $draft_id;
    $manager->manager_name = $request->get('manager_name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForCreate($draft_id, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateNewManager($manager);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function CreateMany(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    $managersJson = $request->get('managers');
    $newManagers = array();

    foreach($managersJson as $managerRequest) {
      $newManager = new Manager();
      $newManager->draft_id = $draft_id;
      $newManager->manager_name = $managerRequest['manager_name'];

      $newManagers[] = $newManager;
    }

    $validity = $app['phpdraft.ManagerValidator']->AreManagersValidForCreate($draft_id, $newManagers);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateManyManagers($draft_id, $newManagers);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function Reorder(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

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

    return $app->json($response, $response->responseType());
  }

  public function Update(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $manager_id = $request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $manager->manager_name = $request->get('name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForUpdate($draft_id, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->UpdateManager($manager);

    return $app->json($response, $response->responseType());
  }

  public function Delete(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $manager_id = $request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    if($manager->draft_id != $draft_id) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$manager_id";

      return $app->json($response, Response::HTTP_BAD_REQUEST); 
    }

    $response = $app['phpdraft.ManagerService']->DeleteManager($manager);

    return $app->json($response, $response->responseType());
  }
}