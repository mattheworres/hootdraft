<?php
namespace PhpDraft\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function GetAll(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');

    return $app->json($app['phpdraft.ManagerRepository']->GetPublicManagers($draft_id));
  }

  public function GetManagerDepthChart(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $manager_id = (int)$request->get('manager_id');

    if (empty($draft_id) || $draft_id == 0) {
      throw new \Exception("Unable to load draft.");
    }

    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    if (!$draft->using_depth_charts) {
      $response = $app['phpdraft.ResponseFactory'](false, array("Draft is not configured to use depth charts."));
      return $app->json($response, $response->responseType());
    }

    $response = $app['phpdraft.DepthChartPositionService']->GetManagerDepthChart($draft, $manager_id);

    return $app->json($response, $response->responseType());
  }
}