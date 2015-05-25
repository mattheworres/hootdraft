<?php
namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function GetAll(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    $viewable = $app['phpdraft.DraftValidator']->IsDraftViewableForUser($draft_id, $request);

    if(!$viewable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Draft marked as private.";

      return $app->json($response);
    }

    return $app->json($app['phpdraft.ManagerRepository']->GetPublicManagers($draft_id));
  }
}