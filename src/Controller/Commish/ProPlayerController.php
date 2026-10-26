<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Services\ProPlayerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_COMMISH) comes from security.yaml. */
class ProPlayerController
{
  public function __construct(private ProPlayerService $service)
  {
  }

  #[Route('/commish/proplayers/search', name: 'commish_proplayers_search', methods: ['GET'])]
  public function search(Request $request): JsonResponse
  {
    $league = $request->query->get('league');

    $searchTerm = (string) $request->query->get('searchTerm');

    // Unset (or empty) fields are null so the repository skips that filter
    $first = $this->optional($request, 'first');
    $last = $this->optional($request, 'last');
    $team = $this->optional($request, 'team');
    $position = $this->optional($request, 'position');

    if (strlen($searchTerm) > 0) {
      $response = $this->service->SearchPlayers($league, $searchTerm);
    } else {
      $response = $this->service->SearchPlayersManual($league, $first, $last, $team, $position);
    }

    return new JsonResponse($response, $response->responseType());
  }

  private function optional(Request $request, string $key): ?string
  {
    $value = (string) $request->query->get($key);

    return strlen($value) == 0 ? null : $value;
  }
}
