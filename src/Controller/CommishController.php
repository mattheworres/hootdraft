<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Services\LoginUserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Public commissioner lookups (the signed-in commissioner endpoints live in Controller/Commish). */
class CommishController
{
  public function __construct(private LoginUserService $users)
  {
  }

  // Declared before the {commish_id} route so "search" is not read as an id
  #[Route('/commissioners/search', name: 'commissioners_search', methods: ['GET'])]
  public function searchPublicCommissioners(Request $request): JsonResponse
  {
    $searchTerm = substr((string) $request->query->get('searchTerm'), 0, 255);

    $response = $this->users->SearchCommissioners($searchTerm);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commissioners/{commish_id}', name: 'commissioners_get', methods: ['GET'])]
  public function getPublicCommissioner(string $commish_id): JsonResponse
  {
    $response = $this->users->GetCommissioner($commish_id);

    return new JsonResponse($response, $response->responseType());
  }
}
