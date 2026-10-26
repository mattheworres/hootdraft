<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Services\PickService;
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
    private PickRepository $picks,
    private PickService $pickService,
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
    $draftId = (int) $draft_id;
    $draft = $this->drafts->Load($draftId);

    $newTrade = new Trade();
    $newTrade->draft_id = $draftId;
    $newTrade->manager1_id = $request->request->get('manager1_id');
    $newTrade->manager2_id = $request->request->get('manager2_id');

    $newTrade->trade_round = $draft->draft_current_round;

    $assets_json = $request->request->all()['trade_assets'] ?? null;

    try {
      if (!is_array($assets_json)) {
        throw new \Exception("Invalid trade data.");
      }

      $newTrade->manager1 = $this->managers->Load($newTrade->manager1_id);
      $newTrade->manager2 = $this->managers->Load($newTrade->manager2_id);

      foreach ($assets_json as $asset_id) {
        $newTradeAsset = new TradeAsset();
        $newTradeAsset->player = $this->picks->Load($asset_id);
        $newTradeAsset->was_drafted = $this->pickService->PickHasBeenSelected($newTradeAsset->player);

        if ($newTradeAsset->player->manager_id == $newTrade->manager1_id) {
          $newTradeAsset->oldmanager_id = $newTrade->manager1_id;
          $newTradeAsset->newmanager_id = $newTrade->manager2_id;
          $newTradeAsset->oldmanager = $newTrade->manager1;
          $newTradeAsset->newmanager = $newTrade->manager2;
        } else if ($newTradeAsset->player->manager_id == $newTrade->manager2_id) {
          $newTradeAsset->oldmanager_id = $newTrade->manager2_id;
          $newTradeAsset->newmanager_id = $newTrade->manager1_id;
          $newTradeAsset->oldmanager = $newTrade->manager2;
          $newTradeAsset->newmanager = $newTrade->manager1;
        } else {
          throw new \Exception("Invalid trade data.");
        }

        $newTrade->trade_assets[] = $newTradeAsset;
      }
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $message = $e->getMessage();
      $response->errors[] = "Unable to build trade: $message";

      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $this->validator->IsTradeValid($draft, $newTrade);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->EnterTrade($draft, $newTrade);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }
}
