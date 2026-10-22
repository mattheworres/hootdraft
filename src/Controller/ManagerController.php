<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Repositories\ManagerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Public manager list. The depth chart route (`/draft/{draft_id}/manager/{manager_id}/depth_chart`) waits for 4.4 (it reads picks). */
class ManagerController
{
  public function __construct(private ManagerRepository $managers)
  {
  }

  #[Route('/draft/{draft_id}/managers', name: 'managers_get_all', methods: ['GET'], defaults: ['_checks' => ['draftViewable']])]
  public function getAll(string $draft_id): JsonResponse
  {
    return new JsonResponse($this->managers->GetPublicManagers((int) $draft_id));
  }
}
