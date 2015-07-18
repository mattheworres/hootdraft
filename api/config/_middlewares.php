<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$draftViewable = function(Symfony\Component\HttpFoundation\Request $request, Silex\Application $app) {
  $draft_id = (int)$request->get('draft_id');

  $viewable = $app['phpdraft.DraftValidator']->IsDraftViewableForUser($draft_id, $request);

  if(!$viewable) {
    $response = new PhpDraftResponse(false, array());
    $response->errors[] = "Draft marked as private.";

    return $app->json($response);
  }
};

$draftSettingUp = function(Symfony\Component\HttpFoundation\Request $request, Silex\Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $setting_up = $app['phpdraft.DraftValidator']->IsDraftSettingUpOrInProgress($draft);

  if(!$setting_up->success) {
    return $app->json($setting_up, $setting_up->responseType());
  }
};

$draftInProgress = function(Symfony\Component\HttpFoundation\Request $request, Silex\Application $app) {
  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $in_progress = $app['phpdraft.DraftValidator']->IsDraftInProgress($draft);

  if(!$in_progress->success) {
    return $app->json($in_progress, $in_progress->responseType());
  }
};

$commishEditableDraft = function(Symfony\Component\HttpFoundation\Request $request, Silex\Application $app) {
  $current_user = $app['phpdraft.LoginUserService']->GetCurrentUser();

  $draft_id = (int)$request->get('draft_id');
  $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

  $editable = $app['phpdraft.DraftValidator']->IsDraftEditableForUser($draft, $current_user);

  if(!$editable) {
    $response = new PhpDraft\Domain\Models\PhpDraftResponse(false, array());
    $response->errors[] = "You do not have permission to this draft.";

    return $app->json($response);
  }
};