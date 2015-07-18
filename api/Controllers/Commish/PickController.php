<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickController {
  public function GetCurrent(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    
    $response = $app['phpdraft.PickService']->GetCurrentPick($draft);

    return $app->json($response, $response->responseType());
  }

  public function Add(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $pick_id = $request->get('pick_id');

    try {
      $pick = $app['phpdraft.PickRepository']->Load($pick_id);

      $pick->first_name = $request->get('first_name');
      $pick->last_name = $request->get('last_name');
      $pick->team = $request->get('team');
      $pick->position = $request->get('position');
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to add pick #$pick_id";

      return $app->json($response, $response->responseType());
    }

    $validity = $app['phpdraft.PickValidator']->IsPickValidForAdd($draft, $pick);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $response = $app['phpdraft.PickService']->AddPick($draft, $pick);

    return $app->json($response, $response->responseType());
  }
}