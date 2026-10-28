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

  #[Route('/admin/sports', name: 'admin_sports_get', methods: ['GET'])]
  public function getSports(): JsonResponse
  {
    return new JsonResponse($this->draftData->GetSports());
  }

  #[Route('/admin/proplayers', name: 'admin_proplayers_upload', methods: ['POST'])]
  public function upload(Request $request): JsonResponse
  {
    //->all() rather than ->get(): get() throws on array values, which would skip our error response
    $sport = $request->request->all()['sport'] ?? null;
    $sport = is_string($sport) ? $sport : null;
    $file = $request->files->get('file');

    if (!$file instanceof UploadedFile) {
      $file = null;
    }

    $validity = $this->validator->IsUploadSportValid($sport, $file);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->service->Upload($sport, $file);

    return new JsonResponse($response, $response->responseType());
  }
}
