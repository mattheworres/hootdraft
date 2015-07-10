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
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();

    $draft = new Draft();

    $draft->commish_id = $current_user->id;
    $draft->commish_name = $current_user->name;

    $draft->sports = $app['phpdraft.DraftDataRepository']->GetSports();
    $draft->styles = $app['phpdraft.DraftDataRepository']->GetStyles();

    return $app->json($draft, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft = new Draft();

    $draft->commish_id = $current_user->id;
    $draft->commish_name = $current_user->name;

    $draft->draft_name = $request->get('name');
    $draft->draft_sport = $request->get('sport');
    $draft->draft_status = "undrafted";
    $draft->draft_style = $request->get('style');
    $draft->draft_rounds = (int)$request->get('rounds');
    $draft->draft_password = $request->get('password');
    $draft->nfl_extended = (bool)$request->get('nfl_extended');

    $validity = $app['phpdraft.DraftValidator']->IsDraftValidForCreateAndUpdate($draft);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.DraftService']->CreateNewDraft($draft);
    $responseType = ($response->success ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

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

    $draft->sports = $app['phpdraft.DraftDataRepository']->GetSports();
    $draft->styles = $app['phpdraft.DraftDataRepository']->GetStyles();
    $draft->statuses = $app['phpdraft.DraftDataRepository']->GetStatuses();

    return $app->json($draft, Response::HTTP_OK);
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

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUp($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $draft->draft_name = $request->get('name');
    $draft->draft_sport = $request->get('sport');
    $draft->draft_style = $request->get('style');
    $draft->draft_rounds = (int)$request->get('rounds');
    $draft->draft_password = $request->get('password');
    $draft->nfl_extended = (bool)$request->get('nfl_extended');

    $validity = $app['phpdraft.DraftValidator']->IsDraftValidForCreateAndUpdate($draft);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.DraftService']->UpdateDraft($draft);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function UpdateStatus(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response);
    }

    $old_status = $draft->draft_status;
    $draft->draft_status = $request->get('status');

    $validity = $app['phpdraft.DraftValidator']->IsDraftStatusValid($draft, $old_status);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.DraftService']->UpdateDraftStatus($draft, $old_status);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function Delete(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response);
    }

    $response = $app['phpdraft.DraftService']->DeleteDraft($draft);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function GetTimers(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response);
    }

    $timers = $app['phpdraft.RoundTimeRepository']->GetDraftTimers($draft_id);

    return $app->json($timers, Response::HTTP_OK);
  }

  public function SetTimers(Application $app, Request $request) {
    $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

    if(!$editable) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You do not have permission to this draft.";

      return $app->json($response);
    }

    $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUpOrInProgress($draft);

    if(!$setting_up->success) {
      return $app->json($setting_up, Response::HTTP_BAD_REQUEST);
    }

    $createModel = new \PhpDraft\Domain\Models\RoundTimeCreateModel();
    $createModel->isRoundTimesEnabled = (bool)$request->get('isRoundTimesEnabled');

    if($createModel->isRoundTimesEnabled) {
      $roundTimesJson = $request->get('roundTimes');

      foreach($roundTimesJson as $roundTimeRequest) {
        $newRoundTime = new \PhpDraft\Domain\Entities\RoundTime();
        $newRoundTime->draft_id = $draft_id;
        $newRoundTime->is_static_time = $roundTimeRequest['is_static_time'] == "true";
        $newRoundTime->draft_round = $newRoundTime->is_static_time ? null : (int)$roundTimeRequest['draft_round'];
        $newRoundTime->round_time_seconds = (int)$roundTimeRequest['round_time_seconds'];

        $createModel->roundTimes[] = $newRoundTime;
      }
    }

    $validity = $app['phpdraft.RoundTimeValidator']->AreRoundTimesValid($createModel);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    //Save round times
    $response = $app['phpdraft.RoundTimeService']->SaveRoundTimes($draft, $createModel);
    $responseType = ($response->success ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }
}