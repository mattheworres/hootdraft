<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Validators\DepthChartPositionValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DepthChartPositionController
{
  public function __construct(
    private DepthChartPositionValidator $validator,
    private DraftDataRepository $draftData,
  ) {
  }

  // Only requires the commish role, handled by security.yaml
  #[Route('/commish/depthchartposition/positions', name: 'commish_depthchart_positions', methods: ['GET'])]
  public function getPositions(Request $request): JsonResponse
  {
    $draft_sport = $request->query->get('draft_sport');

    $validity = $this->validator->IsDraftSportValid($draft_sport);

    if (!$validity->success) {
      return new JsonResponse($validity, JsonResponse::HTTP_BAD_REQUEST);
    }

    $response = new HootDraftResponse(true, array());
    $response->positions = $this->draftData->GetPositions($draft_sport);

    return new JsonResponse($response);
  }
}
