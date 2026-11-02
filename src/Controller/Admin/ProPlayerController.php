<?php

namespace HootDraft\Controller\Admin;

use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Services\ProPlayerService;
use HootDraft\Domain\Validators\ProPlayerValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_ADMIN) comes from security.yaml. */
class ProPlayerController
{
  public function __construct(
    private DraftDataRepository $draftData,
    private ProPlayerValidator $validator,
    private ProPlayerService $service,
  ) {
  }

  #[Route('/admin/leagues', name: 'admin_leagues_get', methods: ['GET'])]
  public function getLeagues(): JsonResponse
  {
    return new JsonResponse($this->draftData->GetLeagues());
  }

  #[Route('/admin/proplayers', name: 'admin_proplayers_upload', methods: ['POST'])]
  public function upload(Request $request): JsonResponse
  {
    //->all() rather than ->get(): get() throws on array values, which would skip our error response
    $league = $request->request->all()['league'] ?? null;
    $league = is_string($league) ? $league : null;
    $file = $request->files->get('file');

    if (!$file instanceof UploadedFile) {
      $file = null;
    }

    $validity = $this->validator->IsUploadLeagueValid($league, $file);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->service->Upload($league, $file);

    return new JsonResponse($response, $response->responseType());
  }
}
