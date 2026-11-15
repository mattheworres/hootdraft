<?php

namespace HootDraft\Controller;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\PickSearchModel;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\PickSearchRepository;
use HootDraft\Domain\Services\PickService;
use HootDraft\Domain\Validators\PickValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Public pick reads (the draft board, search, per-manager and per-round lists) and the public depth chart update. */
class PickController
{
  private const VIEW = ['draftViewable', 'draftInProgressOrCompleted'];
  private const DEFAULT_AMOUNT = 10;
  private const MAX_AMOUNT = 100;

  public function __construct(
    private DraftRepository $drafts,
    private PickRepository $picks,
    private PickSearchRepository $pickSearch,
    private PickService $service,
    private PickValidator $validator,
  ) {
  }

  #[Route('/draft/{draft_id}/picks', name: 'picks_get_all', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getAll(string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $response = $this->service->GetAll($draft);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/draft/{draft_id}/picks/updated', name: 'picks_get_updated', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getUpdated(Request $request, string $draft_id): JsonResponse
  {
    $response = $this->service->LoadUpdatedData((int) $draft_id, (int) $request->query->get('pick_counter'));

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/draft/{draft_id}/pick/{pick_id}/depth_chart/{position_id}', name: 'pick_update_depth_chart', methods: ['PUT'], defaults: ['_checks' => ['draftViewable', 'draftInProgressOrCompletedTenMinutes']])]
  public function updateDepthChart(string $draft_id, string $pick_id, string $position_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    try {
      $pick = $this->picks->Load((int) $pick_id);

      // 0 (a missing or blank id) means the pick is unassigned, which is stored as NULL
      $pick->depth_chart_position_id = (int) $position_id ?: null;
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to edit pick #" . (int) $pick_id;

      return new JsonResponse($response, $response->responseType());
    }

    $validity = $this->validator->IsPickValidForDepthChartUpdate($draft, $pick);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->service->UpdatePickDepthChart($draft, $pick);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/draft/{draft_id}/picks/last', name: 'picks_get_last', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getLast(Request $request, string $draft_id): JsonResponse
  {
    $amount = $this->pickAmount($request);

    return new JsonResponse($this->picks->LoadLastPicks((int) $draft_id, $amount));
  }

  #[Route('/draft/{draft_id}/picks/next', name: 'picks_get_next', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getNext(Request $request, string $draft_id): JsonResponse
  {
    $amount = $this->pickAmount($request);

    $draft = $this->drafts->Load((int) $draft_id);

    return new JsonResponse($this->picks->LoadNextPicks((int) $draft_id, $draft->draft_current_pick, $amount));
  }

  #[Route('/draft/{draft_id}/manager/{manager_id}/picks/all', name: 'picks_get_all_manager', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getAllManagerPicks(string $draft_id, string $manager_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    return new JsonResponse($this->picks->LoadManagerPicks((int) $manager_id, (int) $draft_id, $draft, false));
  }

  #[Route('/draft/{draft_id}/manager/{manager_id}/picks/selected', name: 'picks_get_selected_manager', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getSelectedManagerPicks(string $draft_id, string $manager_id): JsonResponse
  {
    return new JsonResponse($this->picks->LoadManagerPicks((int) $manager_id, (int) $draft_id));
  }

  #[Route('/draft/{draft_id}/round/{draft_round}/picks/all', name: 'picks_get_all_round', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getAllRoundPicks(Request $request, string $draft_id, string $draft_round): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);
    $sortAscending = (bool) $request->query->get('sort_ascending');

    return new JsonResponse($this->picks->LoadRoundPicks($draft, (int) $draft_round, $sortAscending, false));
  }

  #[Route('/draft/{draft_id}/round/{draft_round}/picks/selected', name: 'picks_get_selected_round', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function getSelectedRoundPicks(Request $request, string $draft_id, string $draft_round): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);
    $sortAscending = (bool) $request->query->get('sort_ascending');

    return new JsonResponse($this->picks->LoadRoundPicks($draft, (int) $draft_round, $sortAscending));
  }

  #[Route('/draft/{draft_id}/picks/search', name: 'picks_search', methods: ['GET'], defaults: ['_checks' => self::VIEW])]
  public function searchPicks(Request $request, string $draft_id): JsonResponse
  {
    $draftId = (int) $draft_id;
    $keywords = $request->query->get('keywords');
    $team = $request->query->get('team');
    $position = $request->query->get('position');
    $sort = $request->query->get('sort') ?? 'DESC';

    $pickSearchModel = new PickSearchModel($draftId, $keywords, $team, $position, $sort);

    $pickSearchModel = $this->pickSearch->Search($pickSearchModel);

    return new JsonResponse($pickSearchModel);
  }

  /** The 'amount' query value, defaulted when missing or not positive and capped, since it goes straight into a LIMIT. */
  private function pickAmount(Request $request): int
  {
    $amount = (int) $request->query->get('amount');

    return $amount < 1 ? self::DEFAULT_AMOUNT : min($amount, self::MAX_AMOUNT);
  }
}
