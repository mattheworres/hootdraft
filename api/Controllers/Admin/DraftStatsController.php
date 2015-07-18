<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Draft;

class DraftStatsController
{
  public function Create(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $stats = $app['phpdraft.DraftStatsRepository']->CalculateDraftStatistics($draft);

    return $app->json($stats);
  }
}