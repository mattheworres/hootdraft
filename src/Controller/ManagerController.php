<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Services\DepthChartPositionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Public manager list and a manager's depth chart. */
class ManagerController
{
  public function __construct(
    private ManagerRepository $managers,
    private DraftRepository $drafts,
    private DepthChartPositionService $depthCharts,
  )
  {
  }

  #[Route('/draft/{draft_id}/managers', name: 'managers_get_all', methods: ['GET'], defaults: ['_checks' => ['draftViewable']])]
  public function getAll(string $draft_id): JsonResponse
  {
    return new JsonResponse($this->managers->GetManagersByDraftOrder((int) $draft_id));
  }

  #[Route('/draft/{draft_id}/manager/{manager_id}/depth_chart', name: 'manager_get_depth_chart', methods: ['GET'], defaults: ['_checks' => ['draftViewable', 'draftInProgressOrCompleted']])]
  public function getManagerDepthChart(string $draft_id, string $manager_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    if (!$draft->using_depth_charts) {
      $response = new HootDraftResponse(false, array("Draft is not configured to use depth charts."));

      return new JsonResponse($response, $response->responseType());
    }

    $response = $this->depthCharts->GetManagerDepthChart($draft, (int) $manager_id);

    return new JsonResponse($response, $response->responseType());
  }
}
