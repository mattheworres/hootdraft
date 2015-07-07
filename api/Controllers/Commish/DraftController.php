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

    $app['monolog']->addDebug("Name: " . $request->get('name') . " Sporty: " . $request->get('sport'));

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

    if($draft->draft_status == "completed") {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "The draft is completed, therefore its status cannot be changed.";
      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $old_status = $draft->draft_status;
    $draft->draft_status = $request->get('status');

    if($draft->draft_status == "completed") {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "You cannot set the draft as completed manually.";
      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.DraftValidator']->IsDraftStatusValid($draft);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.DraftService']->UpdateDraftStatus($draft, $old_status);
    $responseType = ($response->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);

    return $app->json($response, $responseType);
  }

  public function Delete(Application $app, Request $request) {
    return $app->json('not implemented', Response::HTTP_NO_CONTENT);
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

  public function SetTimers() {
    return $app->json('not implemented', Response::HTTP_NO_CONTENT);
  }
}