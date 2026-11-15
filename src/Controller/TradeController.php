<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Services\TradeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TradeController
{
  public function __construct(private TradeService $service)
  {
  }

  #[Route('/draft/{draft_id}/trades', name: 'trades_get_all', methods: ['GET'], defaults: ['_checks' => ['draftViewable', 'draftInProgressOrCompleted']])]
  public function getAll(string $draft_id): JsonResponse
  {
    return new JsonResponse($this->service->GetTrades((int) $draft_id), Response::HTTP_OK);
  }
}
