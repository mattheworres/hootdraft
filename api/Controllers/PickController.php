<?php

namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickController {
  public function GetAll(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    return $app->json($app['phpdraft.PickRepository']->LoadAll($draft_id));
  }

  public function GetUpdated(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $pick_counter = (int)$request->get('pick_counter');

    return $app->json($app['phpdraft.PickRepository']->LoadUpdatedPicks($draft_id, $pick_counter));
  }

  public function GetLast(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $amount = (int)$request->get('amount');

    if($amount == 0) {
      $amount = 10;
    }

    return $app->json($app['phpdraft.PickRepository']->LoadLastPicks($draft_id, $amount));
  }

  public function GetNext(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $amount = (int)$request->get('amount');

    if($amount == 0) {
      $amount = 10;
    }

    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    return $app->json($app['phpdraft.PickRepository']->LoadNextPicks($draft_id, $draft->draft_current_pick, $amount));
  }

  public function GetAllManagerPicks(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $manager_id  = (int)$request->get('manager_id');

    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    return $app->json($app['phpdraft.PickRepository']->LoadManagerPicks($manager_id, $draft, false));
  }

  public function GetSelectedManagerPicks(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $manager_id = (int)$request->get('manager_id');

    return $app->json($app['phpdraft.PickRepository']->LoadManagerPicks($manager_id));
  }

  public function GetAllRoundPicks(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $draft_round = (int)$request->get('draft_round');
    $sort_ascending = (bool)$request->get('sort_ascending');

    return $app->json($app['phpdraft.PickRepository']->LoadRoundPicks($draft, $draft_round, $sort_ascending, false));
  }

  public function GetSelectedRoundPicks(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $draft_round = (int)$request->get('draft_round');
    $sort_ascending = (bool)$request->get('sort_ascending');

    return $app->json($app['phpdraft.PickRepository']->LoadRoundPicks($draft, $draft_round, $sort_ascending));
  }

  public function SearchPicks(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $keywords = $request->get('keywords');
    $team = $request->get('team');
    $position = $request->get('position');
    $sort = $request->get('sort');

    $team = isset($team) ? $team : null;
    $position = isset($position) ? $position : null;
    $sort = isset($sort) ? $sort : 'DESC';

    $pickSearchModel = new \PhpDraft\Domain\Models\PickSearchModel($draft_id, $keywords, $team, $position, $sort);

    $pickSearchModel = $app['phpdraft.PickRepository']->SearchStrict($pickSearchModel);
    $pickSearchModel = $app['phpdraft.PickRepository']->SearchLoose($pickSearchModel);

    return $app->json($pickSearchModel);
  }
}