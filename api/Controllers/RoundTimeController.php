<?php

namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Models\PhpDraftResponse;

class RoundTimeController {
  public function GetTimeRemaining(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $response = $app['phpdraft.RoundTimeService']->GetCurrentPickTimeRemaining($draft);

    return $app->json($response, $response->responseType());
  }
}