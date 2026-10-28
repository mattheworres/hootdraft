<?php

namespace HootDraft\Controller\Admin;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_ADMIN) comes from security.yaml. */
class DraftStatsController
{
  public function __construct(private DraftRepository $drafts, private DraftStatsRepository $stats)
  {
  }

  #[Route('/admin/drafts', name: 'admin_drafts_get', methods: ['GET'])]
  public function getDrafts(): JsonResponse
  {
    return new JsonResponse($this->drafts->GetAllCompletedDrafts());
  }

  #[Route('/admin/draft/{draft_id}/stats', name: 'admin_draft_stats_create', methods: ['POST'])]
  public function create(string $draft_id): JsonResponse
  {
    try {
      $draft = $this->drafts->Load((int) $draft_id);
    } catch (\Exception $e) {
      throw new NotFoundHttpException("Unable to load draft.");
    }

    if ($draft->draft_status !== 'complete') {
      throw new ConflictHttpException("Stats can only be calculated for a completed draft.");
    }

    return new JsonResponse($this->stats->CalculateDraftStatistics($draft));
  }
}
