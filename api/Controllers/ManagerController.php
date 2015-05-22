<?php
namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function GetAll(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $password = $request->get('password');

    if(empty($draft_id) || $draft_id == 0) {
      throw new \Exception("Unable to load managers");
    }

    $draft = $app['phpdraft.DraftRepository']->GetPublicDraft($draft_id, $password);

    //If its password protected and the create time is empty, we did not provide the proper password
    if(!$draft->draft_visible && empty($draft->draft_create_time)) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Draft marked as private, invalid/missing password";

      return $app->json($response);
    }

    return $app->json($app['phpdraft.ManagerRepository']->GetPublicManagers($draft_id));
  }
}