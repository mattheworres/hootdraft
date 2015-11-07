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
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id, true);
    
    $response = $app['phpdraft.PickService']->GetCurrentPick($draft);

    return $app->json($response, $response->responseType());
  }

  public function Add(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id, true);
    $pick_id = $request->get('player_id');

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

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function Update(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id, true);
    $pick_id = $request->get('player_id');

    try {
      $pick = $app['phpdraft.PickRepository']->Load($pick_id);

      $pick->first_name = $request->get('first_name');
      $pick->last_name = $request->get('last_name');
      $pick->team = $request->get('team');
      $pick->position = $request->get('position');
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to edit pick #$pick_id";

      return $app->json($response, $response->responseType());
    }

    $validity = $app['phpdraft.PickValidator']->IsPickValidForUpdate($draft, $pick);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $response = $app['phpdraft.PickService']->UpdatePick($draft, $pick);

    return $app->json($response, $response->responseType());
  }

  public function AlreadyDrafted(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $first_name = $request->get('first_name');
    $last_name = $request->get('last_name');

    $response = $app['phpdraft.PickService']->AlreadyDrafted($draft_id, $first_name, $last_name);

    return $app->json($response, $response->responseType());
  }

  //This will also be used as the "Create" method to populate a list of valid rounds for the view to use
  public function GetLast5(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $response = new PhpDraftResponse();

    try {
      $draft = $app['phpdraft.DraftRepository']->Load($draft_id, true);

      $response->draft_rounds = $draft->draft_rounds;
      $response->last_5_picks = $app['phpdraft.PickRepository']->LoadLastPicks($draft_id, 5);
      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load last 5 picks.";
    }

    return $app->json($response, $response->responseType());
  }

  public function GetByRound(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $round = (int)$request->get('draft_round');
    $response = new PhpDraftResponse();

    try {
      $response->round = $round;
      $response->round_picks = $app['phpdraft.PickRepository']->LoadRoundPicks($draft, $round, false, true);
      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load round #$round's picks";
    }

    return $app->json($response, $response->responseType());
  }
}