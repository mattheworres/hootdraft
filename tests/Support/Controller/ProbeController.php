<?php

namespace HootDraft\Tests\Support\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Test-only stand-ins for protected URLs, until the real /commish and /admin controllers are ported. */
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
}
