<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\DepthChartDisplayModel;

class DepthChartPositionService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetManagerDepthChart(Draft $draft, $manager_id) {
    $response = $this->app['phpdraft.ResponseFactory'](true, array());

    try {
      $draftPositions = $this->app['phpdraft.DepthChartPositionRepository']->LoadAll($draft->draft_id);

      $response->manager_id = $manager_id;
      $response->draftPositions = array();

      //Rather than hit the DB with 5 separate queries each depth chart request, hit it once and we cycle
      //through the PHP array in-memory five times performing either is_null checks or integer equality
      //checks:
      $allManagerPicks = $this->app['phpdraft.PickRepository']->LoadManagerPicks($manager_id, $draft, true);

      $unassignedPicks = array();

      foreach($allManagerPicks as $pick) {
        if(is_null($pick->depth_chart_position_id)) {
          $unassignedPicks[] = $pick;
        }
      }

      $response->draftPositions[] = new DepthChartDisplayModel(null, 'Unassigned', null, $unassignedPicks);

      foreach($draftPositions as $position) {
        $picks = array();

        foreach($allManagerPicks as $pick) {
          if($pick->depth_chart_position_id == $position->id) {
            $picks[] = $pick;
          }
        }

        $response->draftPositions[] = new DepthChartDisplayModel($position->id, $position->position, $position->slots, $picks);
      }
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors[] = $e->getMessage();

      return $response;
    }

    return $response;
  }
}