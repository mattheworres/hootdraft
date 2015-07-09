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

      return $app->json($response);
    }

    $managers = $app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft->draft_id);

    return $app->json($managers, Response::HTTP_OK);
  }

  public function Update(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response);
    }

    return $app->json('not implemented', Response::HTTP_BAD_REQUEST);
  }
}