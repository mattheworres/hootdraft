<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use PhpDraft\Domain\Models\PhpDraftResponse;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app->error(function (\Exception $e, $code) use($app) {
  return $app->json(array("error" => $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
});

//If we get application/json, decode the data so controllers can use it
$app->before(function (Request $request) {
  if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
    $data = json_decode($request->getContent(), true);
    $request->request->replace(is_array($data) ? $data : array());
  }
});

//Ensure the user is allowed to see the draft
$draftViewable = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');

  $viewable = $app['phpdraft.DraftValidator']->IsDraftViewableForUser($draft_id, $request);

  if(!$viewable) {
    $response = new PhpDraftResponse(false, array());
    $response->errors[] = "Draft marked as private.";

    return $app->json(array($response), $response->responseType());
  }
};

$draftSettingUp = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUpOrInProgress($draft);

  if(!$setting_up->success) {
    return $app->json(array($setting_up), $setting_up->responseType());
  }
};

$draftInProgress = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $in_progress = $app['phpdraft.DraftValidator']->IsDraftInProgress($draft);

  if(!$in_progress->success) {
    return $app->json(array($in_progress), $in_progress->responseType());
  }
};

$draftInProgressOrCompleted = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $in_progress = $app['phpdraft.DraftValidator']->IsDraftInProgressOrComplete($draft);

  if(!$in_progress->success) {
    return $app->json(array($in_progress), $in_progress->responseType());
  }
};

//To allow a ten minute grace period after completion of a draft
$draftInProgressOrCompletedTenMinutes = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $grace_period = $app['phpdraft.DraftValidator']->IsDraftInProgressOrCompletedInLessThanTen($draft);

  if(!$grace_period->success) {
    return $app->json(array($grace_period), $grace_period->responseType());
  }
};

$draftCompleted = function(Request $request, Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $completed = $app['phpdraft.DraftValidator']->IsDraftComplete($draft);

  if(!$completed->success) {
    return $app->json(array($completed), $completed->responseType());
  }
};

$commishEditableDraft = function(Request $request, Application $app) {
  $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();

  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

  if(!$editable) {
    $response = new PhpDraft\Domain\Models\PhpDraftResponse(false, array());
    $response->errors[] = "You do not have permission to this draft.";

    return $app->json(array($response), $response->responseType());
  }
};