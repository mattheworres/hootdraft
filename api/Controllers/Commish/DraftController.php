<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Draft;

class DraftController
{
  public function GetCreate(Application $app, Request $request) {
    $currentUser = $app['phpdraft.LoginUserService']->GetCurrentUser();

    $draft = new Draft();

    $draft->draft_style = 'serpentine';
    $draft->commish_id = $currentUser->id;
    $draft->commish_name = $currentUser->name;

    $draft->sports = $app['phpdraft.DraftDataRepository']->GetSports();
    $draft->styles = $app['phpdraft.DraftDataRepository']->GetStyles();

    return $app->json($draft, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $currentUser = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft = new Draft();

    $draft->commish_id = $currentUser->id;
    $draft->commish_name = $currentUser->name;

    $draft->draft_name = $request->get('name');
    $draft->draft_sport = $request->get('sport');
    $draft->draft_status = "undrafted";
    $draft->draft_style = $request->get('style');
    $draft->draft_rounds = (int)$request->get('rounds');
    $draft->draft_password = $request->get('password');
    $draft->using_depth_charts = $request->get('using_depth_charts') == true ? 1 : 0;

    $validity = $app['phpdraft.DraftValidator']->IsDraftValidForCreateAndUpdate($draft);

    if (!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

    if ($draft->using_depth_charts == 1) {
      $createPositionsModel = $this->_BuildDepthChartPositionModel($request);

      $positionValidity = $app['phpdraft.DepthChartPositionValidator']->AreDepthChartPositionsValid($createPositionsModel);

      if (!$positionValidity->success) {
        return $app->json($positionValidity, Response::HTTP_BAD_REQUEST);
      }
    }

    $response = $app['phpdraft.DraftService']->CreateNewDraft($draft, $createPositionsModel);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function Get(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $draft->sports = $app['phpdraft.DraftDataRepository']->GetSports();
    $draft->styles = $app['phpdraft.DraftDataRepository']->GetStyles();
    $draft->statuses = $app['phpdraft.DraftDataRepository']->GetStatuses();
    $draft->depthChartPositions = $draft->using_depth_charts == 1
      ? $app['phpdraft.DepthChartPositionRepository']->LoadAll($draftId)
      : array();

    return $app->json($draft, Response::HTTP_OK);
  }

  public function Update(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $draft->draft_name = $request->get('name');
    $draft->draft_sport = $request->get('sport');
    $draft->draft_style = $request->get('style');
    $draft->draft_rounds = (int)$request->get('rounds');
    $draft->draft_password = $request->get('password');
    $draft->using_depth_charts = $request->get('using_depth_charts') == true ? 1 : 0;

    $validity = $app['phpdraft.DraftValidator']->IsDraftValidForCreateAndUpdate($draft);

    if (!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

    if ($draft->using_depth_charts == 1) {
      $createPositionsModel = $this->_BuildDepthChartPositionModel($request);

      $positionValidity = $app['phpdraft.DepthChartPositionValidator']->AreDepthChartPositionsValid($createPositionsModel);

      if (!$positionValidity->success) {
        return $app->json($positionValidity, Response::HTTP_BAD_REQUEST);
      }
    }

    $response = $app['phpdraft.DraftService']->UpdateDraft($draft, $createPositionsModel);

    return $app->json($response, $response->responseType());
  }

  public function UpdateStatus(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $oldStatus = $draft->draft_status;
    $draft->draft_status = $request->get('status');

    $validity = $app['phpdraft.DraftValidator']->IsDraftStatusValid($draft, $oldStatus);

    if (!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.DraftService']->UpdateDraftStatus($draft, $oldStatus);

    return $app->json($response, $response->responseType());
  }

  public function Delete(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $response = $app['phpdraft.DraftService']->DeleteDraft($draft);

    return $app->json($response, $response->responseType());
  }

  public function GetTimers(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $timers = $app['phpdraft.RoundTimeRepository']->GetDraftTimers($draft);

    return $app->json($timers, Response::HTTP_OK);
  }

  public function SetTimers(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $createModel = new \PhpDraft\Domain\Models\RoundTimeCreateModel();
    $createModel->isRoundTimesEnabled = (bool)$request->get('isRoundTimesEnabled');

    if ($createModel->isRoundTimesEnabled) {
      $roundTimesJson = $request->get('roundTimes');

      foreach ($roundTimesJson as $roundTimeRequest) {
        $newRoundTime = new \PhpDraft\Domain\Entities\RoundTime();
        $newRoundTime->draft_id = $draftId;
        $newRoundTime->is_static_time = $roundTimeRequest['is_static_time'] == "true" ? 1 : 0;
        $newRoundTime->draft_round = $newRoundTime->is_static_time ? null : (int)$roundTimeRequest['draft_round'];
        $newRoundTime->round_time_seconds = (int)$roundTimeRequest['round_time_seconds'];

        $createModel->roundTimes[] = $newRoundTime;
      }
    }

    $validity = $app['phpdraft.RoundTimeValidator']->AreRoundTimesValid($createModel);

    if (!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    //Save round times
    $response = $app['phpdraft.RoundTimeService']->SaveRoundTimes($draft, $createModel);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  private function _BuildDepthChartPositionModel(Request $request, $draftId = null) {
    $createPositionsModel = new \PhpDraft\Domain\Models\DepthChartPositionCreateModel();
    $createPositionsModel->depthChartEnabled = true;

    $depthChartPositionJson = $request->get('depthChartPositions');
    $display_order = 0;

    foreach ($depthChartPositionJson as $depthChartPositionRequest) {
      $depthChartPosition = new \PhpDraft\Domain\Entities\DepthChartPosition();
      $depthChartPosition->draft_id = $draftId;
      $depthChartPosition->position = $depthChartPositionRequest['position'];
      $depthChartPosition->slots = (int)$depthChartPositionRequest['slots'];
      $depthChartPosition->display_order = $display_order++;

      $createPositionsModel->positions[] = $depthChartPosition;
    }

    return $createPositionsModel;
  }
}