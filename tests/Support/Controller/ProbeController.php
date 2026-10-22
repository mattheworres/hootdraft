<?php

namespace HootDraft\Tests\Support\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test-only stand-ins for protected URLs, until the real /commish and /admin controllers are ported, and
 * for the draft route checks, which are also exercised through the real controllers as they are ported.
 */
class ProbeController
{
  #[Route('/commish/_probe', methods: ['GET'])]
  public function commish(): JsonResponse
  {
    return new JsonResponse(['probe' => 'commish']);
  }

  #[Route('/admin/_probe', methods: ['GET'])]
  public function admin(): JsonResponse
  {
    return new JsonResponse(['probe' => 'admin']);
  }

  #[Route('/_probe/draft/{draft_id}/viewable', methods: ['GET'], defaults: ['_checks' => ['draftViewable']])]
  public function viewable(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }

  #[Route('/_probe/draft/{draft_id}/setting-up', methods: ['GET'], defaults: ['_checks' => ['draftSettingUp']])]
  public function settingUp(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }

  #[Route('/_probe/draft/{draft_id}/in-progress', methods: ['GET'], defaults: ['_checks' => ['draftInProgress']])]
  public function inProgress(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }

  #[Route('/_probe/draft/{draft_id}/in-progress-or-completed', methods: ['GET'], defaults: ['_checks' => ['draftInProgressOrCompleted']])]
  public function inProgressOrCompleted(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }

  #[Route('/_probe/draft/{draft_id}/ten-minutes', methods: ['GET'], defaults: ['_checks' => ['draftInProgressOrCompletedTenMinutes']])]
  public function tenMinutes(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }

  #[Route('/commish/_probe/draft/{draft_id}/editable', methods: ['GET'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function editable(): JsonResponse
  {
    return new JsonResponse(['probe' => 'passed']);
  }
}
