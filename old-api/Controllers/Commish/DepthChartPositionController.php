<?php
namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\DepthChartPosition;

class DepthChartPositionController
{
  public function GetPositions(Application $app, Request $request) {
    $draft_sport = $request->get('draft_sport');

    $validity = $app['phpdraft.DepthChartPositionValidator']->IsDraftSportValid($draft_sport);

    if (!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ResponseFactory'](true, array());
    $response->positions = array();
    $response->positions = $app['phpdraft.DraftDataRepository']->GetPositions($draft_sport);

    return $app->json($response);
  }
}