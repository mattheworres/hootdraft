<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Services\RoundTimeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RoundTimeController
{
  public function __construct(private DraftRepository $drafts, private RoundTimeService $service)
  {
  }

  #[Route('/draft/{draft_id}/timer/remaining', name: 'draft_timer_remaining', methods: ['GET'], defaults: ['_checks' => ['draftViewable', 'draftInProgress']])]
  public function getTimeRemaining(string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $response = $this->service->GetCurrentPickTimeRemaining($draft);

    return new JsonResponse($response, $response->responseType());
  }
}
