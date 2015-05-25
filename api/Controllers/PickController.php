<?php

namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickController {
  public function GetUpdated(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $pick_counter = (int)$request->get('pick_counter');

    $viewable = $app['phpdraft.DraftValidator']->IsDraftViewableForUser($draft_id, $request);

    if(!$viewable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Draft marked as private.";

      return $app->json($response);
    }

    return $app->json($app['phpdraft.PickRepository']->LoadUpdatedPicks($draft_id, $pick_counter));
  }
}