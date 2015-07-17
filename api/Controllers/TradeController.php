<?php

namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeController {
  public function GetAll(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    return $app->json($app['phpdraft.TradeRepository']->GetTrades($draft_id), Response::HTTP_OK);
  }
}