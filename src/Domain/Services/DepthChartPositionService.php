<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\DepthChartDisplayModel;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\PickRepository;

class DepthChartPositionService {
  public function __construct(
    private DepthChartPositionRepository $depthChartPositions,
    private PickRepository $pickRepository,
  ) {
  }

  public function GetManagerDepthChart(Draft $draft, $manager_id) {
    $response = new HootDraftResponse(true, array());

    try {
      $depthChartPositions = $this->depthChartPositions->LoadAll($draft->draft_id);

      $response->manager_id = $manager_id;
      $response->depthChartPositions = array();

      //Rather than hit the DB with 5 separate queries each depth chart request, hit it once and we cycle
      //through the PHP array in-memory five times performing either is_null checks or integer equality
      //checks:
      $allManagerPicks = $this->pickRepository->LoadManagerPicks($manager_id, $draft->draft_id, $draft, true);

      $unassignedPicks = array();

      foreach ($allManagerPicks as $pick) {
        if (is_null($pick->depth_chart_position_id)) {
          $unassignedPicks[] = $pick;
        }
      }

      $response->depthChartPositions[] = new DepthChartDisplayModel(null, 'Unassigned', null, $unassignedPicks);

      foreach ($depthChartPositions as $position) {
        $picks = array();

        foreach ($allManagerPicks as $pick) {
          if ($pick->depth_chart_position_id == $position->id) {
            $picks[] = $pick;
          }
        }

        $response->depthChartPositions[] = new DepthChartDisplayModel($position->id, $position->position, $position->slots, $picks);
      }
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors[] = $e->getMessage();

      return $response;
    }

    return $response;
  }
}
