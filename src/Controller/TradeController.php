<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Repositories\TradeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TradeController
{
  public function __construct(private TradeRepository $trades)
  {
  }

  #[Route('/draft/{draft_id}/trades', name: 'trades_get_all', methods: ['GET'], defaults: ['_checks' => ['draftViewable', 'draftInProgressOrCompleted']])]
  public function getAll(string $draft_id): JsonResponse
  {
    return new JsonResponse($this->trades->GetTrades((int) $draft_id), Response::HTTP_OK);
  }
}
