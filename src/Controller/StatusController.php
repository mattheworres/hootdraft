<?php

namespace HootDraft\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatusController
{
  // Placeholder proving the new kernel boots. Real controllers are ported in step 4.
  #[Route('/', name: 'api_status', methods: ['GET'])]
  public function status(): JsonResponse
  {
    return new JsonResponse(['name' => 'HootDraft API', 'status' => 'ok']);
  }
}
