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
    return $app->json('not implemented', Response::HTTP_NO_CONTENT);//HTTP_CREATED
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

    return $app->json('not implemented', Response::HTTP_NO_CONTENT);
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