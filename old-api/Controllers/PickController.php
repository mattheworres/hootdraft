<?php

namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\PickSearchModel;

class PickController {
  public function GetAll(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $response = $app['phpdraft.PickService']->GetAll($draft);

    return $app->json($response, $response->responseType());
  }

  public function GetUpdated(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $pickCounter = (int)$request->get('pick_counter');

    $response = $app['phpdraft.PickService']->LoadUpdatedData($draftId, $pickCounter);

    return $app->json($response, $response->responseType());
  }

  public function GetLast(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $amount = (int)$request->get('amount');

    if ($amount == 0) {
      $amount = 10;
    }

    return $app->json($app['phpdraft.PickRepository']->LoadLastPicks($draftId, $amount));
  }

  public function GetNext(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $amount = (int)$request->get('amount');

    if ($amount == 0) {
      $amount = 10;
    }

    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    return $app->json($app['phpdraft.PickRepository']->LoadNextPicks($draftId, $draft->draft_current_pick, $amount));
  }

  public function GetAllManagerPicks(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $managerId = (int)$request->get('manager_id');

    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    return $app->json($app['phpdraft.PickRepository']->LoadManagerPicks($managerId, $draft, false));
  }

  public function GetSelectedManagerPicks(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $managerId = (int)$request->get('manager_id');

    return $app->json($app['phpdraft.PickRepository']->LoadManagerPicks($managerId));
  }

  public function GetAllRoundPicks(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);
    $draftRound = (int)$request->get('draft_round');
    $sortAscending = (bool)$request->get('sort_ascending');

    return $app->json($app['phpdraft.PickRepository']->LoadRoundPicks($draft, $draftRound, $sortAscending, false));
  }

  public function GetSelectedRoundPicks(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);
    $draftRound = (int)$request->get('draft_round');
    $sortAscending = (bool)$request->get('sort_ascending');

    return $app->json($app['phpdraft.PickRepository']->LoadRoundPicks($draft, $draftRound, $sortAscending));
  }

  public function SearchPicks(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $keywords = $request->get('keywords');
    $team = $request->get('team');
    $position = $request->get('position');
    $sort = $request->get('sort');

    $team = isset($team) ? $team : null;
    $position = isset($position) ? $position : null;
    $sort = isset($sort) ? $sort : 'DESC';

    $pickSearchModel = new PickSearchModel($draftId, $keywords, $team, $position, $sort);

    $pickSearchModel = $app['phpdraft.PickSearchRepository']->SearchStrict($pickSearchModel);
    $pickSearchModel = $app['phpdraft.PickSearchRepository']->SearchLoose($pickSearchModel);

    #If there's a space and no matches so far, create another searches where we manually split them firstname/lastname by sace automatically
    $splitNameAutomatically = count($pickSearchModel->player_results) == 0 && strpos($keywords, " ") != false;

    if ($splitNameAutomatically) {
      $pickSearchModel = new PickSearchModel($draftId, $keywords, $team, $position, $sort);
      $names = explode(" ", $keywords, 2);
      $pickSearchModel = $app['phpdraft.PickSearchRepository']->SearchSplit($pickSearchModel, $names[0], $names[1]);
    }

    return $app->json($pickSearchModel);
  }

  public function UpdateDepthChart(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $pick_id = (int)$request->get('pick_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    try {
      $pick = $app['phpdraft.PickRepository']->Load($pick_id);

      $pick->depth_chart_position_id = (int)$request->get('position_id');
    } catch (\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to edit pick #$pick_id";

      return $app->json($response, $response->responseType());
    }

    $validity = $app['phpdraft.PickValidator']->IsPickValidForDepthChartUpdate($draft, $pick);

    if (!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $response = $app['phpdraft.PickService']->UpdatePickDepthChart($draft, $pick);

    return $app->json($response, $response->responseType());
  }
}
