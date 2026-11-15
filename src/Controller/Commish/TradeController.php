<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Services\TradeService;
use HootDraft\Domain\Validators\TradeValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_COMMISH) comes from security.yaml; ownership of the draft from the commishEditableDraft check. */
class TradeController
{
  private const EDIT = ['commishEditableDraft', 'draftInProgress'];

  public function __construct(
    private DraftRepository $drafts,
    private ManagerRepository $managers,
    private TradeValidator $validator,
    private TradeService $service,
  ) {
  }

  #[Route('/commish/draft/{draft_id}/manager/{manager_id}/assets', name: 'commish_trade_get_assets', methods: ['GET'], defaults: ['_checks' => self::EDIT])]
  public function getAssets(string $draft_id, string $manager_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $managerId = (int) $manager_id;

    try {
      $manager = $this->managers->Load($managerId);
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$managerId";
      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $this->validator->IsManagerValidForAssetRetrieval($draft, $manager);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $assets = $this->service->GetManagerAssets($draft, $manager->manager_id);

    return new JsonResponse($assets, Response::HTTP_OK);
  }

  #[Route('/commish/draft/{draft_id}/trade', name: 'commish_trade_create', methods: ['POST'], defaults: ['_checks' => self::EDIT])]
  public function create(Request $request, string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $built = $this->service->BuildTrade(
      $draft,
      $request->request->get('manager1_id'),
      $request->request->get('manager2_id'),
      $request->request->all()['trade_assets'] ?? null,
    );

    if ($built->trade === null) {
      return new JsonResponse(new HootDraftResponse(false, $built->errors), Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->EnterTrade($draft, $built->trade);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }
}
