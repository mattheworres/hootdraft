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

    $password = $request->get('password');

    $draft = $app['phpdraft.DraftRepository']->GetPublicDraft($draft_id, $password);

    return $app->json($draft);
  }

  public function GetAll(Application $app) {
    //TODO: Add paging for datatables
    $drafts = $app['phpdraft.DraftRepository']->GetPublicDrafts();

    return $app->json($drafts);
  }
}