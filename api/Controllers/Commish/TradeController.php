<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeController {
  public function GetAssets(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $in_progress = $app['phpdraft.DraftValidator']->IsDraftInProgress($draft);

    if(!$in_progress->success) {
      return $app->json($in_progress, Response::HTTP_BAD_REQUEST);
    }

    $manager_id = (int)$request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$manager_id";
      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.TradeValidator']->IsManagerValidForAssetRetrieval($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $assets = $app['phpdraft.TradeService']->GetManagerAssets($manager->manager_id);

    return $app->json($assets, Response::HTTP_OK);
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

    $in_progress = $app['phpdraft.DraftValidator']->IsDraftInProgress($draft);

    if(!$in_progress->success) {
      return $app->json($in_progress, Response::HTTP_BAD_REQUEST);
    }

    /*$manager = new Manager();
    $manager->draft_id = $draft_id;
    $manager->manager_name = $request->get('manager_name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForCreate($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateNewManager($manager);
    $responseType = ($response->success ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);*/
    return $app->json('not implemented bro');
  }
}