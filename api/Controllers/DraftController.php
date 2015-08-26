<?php
namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use \PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\Pick;

class DraftController {
  public function Get(Application $app, Request $request) {
    $draft_id = (int)$request->get('id');

    if(empty($draft_id) || $draft_id == 0) {
      throw new \Exception("Unable to load draft.");
    }

    //Need to put it in headers so the client can easily add it to all requests (similar to token)
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');

    $draft = $app['phpdraft.DraftRepository']->GetPublicDraft($request, $draft_id, $password);

    return $app->json($draft);
  }

  public function GetAll(Application $app, Request $request) {
    //TODO: Add paging for datatables
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');
    $drafts = $app['phpdraft.DraftRepository']->GetPublicDrafts($request, $password);

    return $app->json($drafts);
  }

  public function GetAllByCommish(Application $app, Request $request) {
    $commish_id = $request->get('commish_id');
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');

    $drafts = $app['phpdraft.DraftRepository']->GetPublicDraftsByCommish($request, $commish_id, $password);

    return $app->json($drafts);
  }

  public function GetStats(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $response = $app['phpdraft.DraftService']->GetDraftStats($draft_id);

    return $app->json($response, $response->responseType());
  }
}