<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ProPlayerController {
  public function Search(Application $app, Request $request) {
    $league = $request->get('league');
    $first = $request->get('first');
    $last = $request->get('last');
    $team = $request->get('team');
    $position = $request->get('position');
    
    $first = strlen($first) == 0 ? "NA" : $first;
    $last = strlen($last) == 0 ? "NA" : $last;
    $team = strlen($team) == 0 ? "NA" : $team;
    $position = strlen($position) == 0 ? "NA" : $position;
    
    $response = $app['phpdraft.ProPlayerService']->SearchPlayers($league, $first, $last, $team, $position);

    return $app->json($response, $response->responseType());
  }
}