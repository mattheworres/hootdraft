<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Repositories\DraftRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Public draft reads. `/draft/{draft_id}/stats` waits for the stats repository (4.6). */
class DraftController
{
  public function __construct(
    private DraftRepository $drafts,
    #[Autowire('%hootdraft.draft_password_header%')] private string $draftPasswordHeader,
  ) {
  }

  #[Route('/draft/{id}', name: 'draft_get', methods: ['GET'])]
  public function get(Request $request, string $id): JsonResponse
  {
    $draft_id = (int) $id;
    $getDraftData = $request->query->get('get_draft_data') == 'true';

    if (empty($draft_id) || $draft_id == 0) {
      throw new NotFoundHttpException("Unable to load draft.");
    }

    //Need to put it in headers so the client can easily add it to all requests (similar to the old token)
    $password = $request->headers->get($this->draftPasswordHeader, '');

    return new JsonResponse($this->drafts->GetPublicDraft($draft_id, $getDraftData, $password));
  }

  #[Route('/drafts', name: 'drafts_get_all', methods: ['GET'])]
  public function getAll(Request $request): JsonResponse
  {
    $password = $request->headers->get($this->draftPasswordHeader, '');

    return new JsonResponse($this->drafts->GetPublicDrafts($password));
  }

  #[Route('/drafts/{commish_id}', name: 'drafts_get_by_commish', methods: ['GET'])]
  public function getAllByCommish(Request $request, string $commish_id): JsonResponse
  {
    $password = $request->headers->get($this->draftPasswordHeader, '');

    return new JsonResponse($this->drafts->GetPublicDraftsByCommish($commish_id, $password));
  }
}
