<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\RoundTime;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\RoundTimeCreateModel;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Domain\Services\RoundTimeService;
use HootDraft\Domain\Validators\DepthChartPositionValidator;
use HootDraft\Domain\Validators\DraftValidator;
use HootDraft\Domain\Validators\RoundTimeValidator;
use HootDraft\Security\CurrentUser;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_COMMISH) comes from security.yaml; ownership of the draft from the commishEditableDraft check. */
class DraftController
{
  public function __construct(
    private DraftRepository $drafts,
    private DraftDataRepository $draftData,
    private DraftValidator $validator,
    private DraftService $service,
    private DepthChartPositionRepository $depthChartPositions,
    private DepthChartPositionValidator $depthChartValidator,
    private RoundTimeRepository $roundTimes,
    private RoundTimeValidator $roundTimeValidator,
    private RoundTimeService $roundTimeService,
    private CurrentUser $currentUser,
  ) {
  }

  // Declared before the {draft_id} route so "create" is not read as an id
  #[Route('/commish/draft/create', name: 'commish_draft_get_create', methods: ['GET'])]
  public function getCreate(): JsonResponse
  {
    $currentUser = $this->currentUser->get();

    $draft = new Draft();

    $draft->draft_style = 'serpentine';
    $draft->commish_id = $currentUser->getId();
    $draft->commish_name = $currentUser->getName();
    $draft->draft_sport = 'NFL';

    $draft->sports = $this->draftData->GetSports();
    $draft->styles = $this->draftData->GetStyles();

    return new JsonResponse($draft, Response::HTTP_OK);
  }

  #[Route('/commish/draft', name: 'commish_draft_create', methods: ['POST'])]
  public function create(Request $request): JsonResponse
  {
    $payload = $request->getPayload();
    $currentUser = $this->currentUser->get();
    $draft = new Draft();

    $draft->commish_id = $currentUser->getId();
    $draft->commish_name = $currentUser->getName();

    $draft->draft_name = $payload->get('name');
    $draft->draft_sport = $payload->get('sport');
    $draft->draft_status = "undrafted";
    $draft->draft_style = $payload->get('style');
    $draft->draft_rounds = (int) $payload->get('rounds');
    $draft->draft_password = $payload->get('password');
    $draft->using_depth_charts = $payload->get('using_depth_charts') == true ? 1 : 0;

    $validity = $this->validator->IsDraftValidForCreateAndUpdate($draft);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

    if ($draft->using_depth_charts == 1) {
      $createPositionsModel = $this->buildDepthChartPositionModel($payload);

      $positionValidity = $this->depthChartValidator->AreDepthChartPositionsValid($createPositionsModel);

      if (!$positionValidity->success) {
        return new JsonResponse($positionValidity, Response::HTTP_BAD_REQUEST);
      }
    }

    $response = $this->service->CreateNewDraft($draft, $createPositionsModel);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }

  #[Route('/commish/draft/{draft_id}', name: 'commish_draft_get', methods: ['GET'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function get(string $draft_id): JsonResponse
  {
    $draftId = (int) $draft_id;
    $draft = $this->drafts->Load($draftId);

    $draft->sports = $this->draftData->GetSports();
    $draft->styles = $this->draftData->GetStyles();
    $draft->statuses = $this->draftData->GetStatuses();
    $draft->depthChartPositions = $draft->using_depth_charts == 1
      ? $this->depthChartPositions->LoadAll($draftId)
      : array();

    return new JsonResponse($draft, Response::HTTP_OK);
  }

  #[Route('/commish/draft/{draft_id}', name: 'commish_draft_update', methods: ['PUT'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function update(Request $request, string $draft_id): JsonResponse
  {
    $payload = $request->getPayload();
    $draft = $this->drafts->Load((int) $draft_id);

    // Picks reference depth chart positions once the draft starts, so the depth chart settings can only change while
    // it is being set up. After that the rest of the form saves and the depth chart fields in it are ignored.
    $depthChartsEditable = $this->service->DraftSettingUp($draft);
    $usingDepthCharts = $draft->using_depth_charts;

    $draft->draft_name = $payload->get('name');
    $draft->draft_sport = $payload->get('sport');
    $draft->draft_style = $payload->get('style');
    $draft->draft_rounds = (int) $payload->get('rounds');
    $draft->draft_password = $payload->get('password');
    $draft->using_depth_charts = $depthChartsEditable
      ? ($payload->get('using_depth_charts') == true ? 1 : 0)
      : $usingDepthCharts;

    $validity = $this->validator->IsDraftValidForCreateAndUpdate($draft);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

    if ($depthChartsEditable && $draft->using_depth_charts == 1) {
      $createPositionsModel = $this->buildDepthChartPositionModel($payload);

      $positionValidity = $this->depthChartValidator->AreDepthChartPositionsValid($createPositionsModel);

      if (!$positionValidity->success) {
        return new JsonResponse($positionValidity, Response::HTTP_BAD_REQUEST);
      }
    }

    $response = $this->service->UpdateDraft($draft, $createPositionsModel);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/status', name: 'commish_draft_update_status', methods: ['PUT'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function updateStatus(Request $request, string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $oldStatus = $draft->draft_status;
    $draft->draft_status = $request->getPayload()->get('status');

    $validity = $this->validator->IsDraftStatusValid($draft, $oldStatus);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $this->service->UpdateDraftStatus($draft, $oldStatus);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}', name: 'commish_draft_delete', methods: ['DELETE'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function delete(string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $response = $this->service->DeleteDraft($draft);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/timers', name: 'commish_draft_get_timers', methods: ['GET'], defaults: ['_checks' => ['commishEditableDraft']])]
  public function getTimers(string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    return new JsonResponse($this->roundTimes->GetDraftTimers($draft), Response::HTTP_OK);
  }

  #[Route('/commish/draft/{draft_id}/timers', name: 'commish_draft_set_timers', methods: ['POST'], defaults: ['_checks' => ['commishEditableDraft', 'draftSettingUpOrInProgress']])]
  public function setTimers(Request $request, string $draft_id): JsonResponse
  {
    $payload = $request->getPayload();
    $draftId = (int) $draft_id;
    $draft = $this->drafts->Load($draftId);

    $createModel = new RoundTimeCreateModel();
    $createModel->isRoundTimesEnabled = (bool) $payload->get('isRoundTimesEnabled');

    if ($createModel->isRoundTimesEnabled) {
      $roundTimesJson = $payload->all()['roundTimes'] ?? [];

      foreach (is_array($roundTimesJson) ? $roundTimesJson : [] as $roundTimeRequest) {
        if (!is_array($roundTimeRequest)) {
          continue;
        }

        $newRoundTime = new RoundTime();
        $newRoundTime->draft_id = $draftId;
        $newRoundTime->is_static_time = ($roundTimeRequest['is_static_time'] ?? null) == "true" ? 1 : 0;
        $newRoundTime->draft_round = $newRoundTime->is_static_time ? null : (int) ($roundTimeRequest['draft_round'] ?? 0);
        $newRoundTime->round_time_seconds = (int) ($roundTimeRequest['round_time_seconds'] ?? 0);

        $createModel->roundTimes[] = $newRoundTime;
      }
    }

    $validity = $this->roundTimeValidator->AreRoundTimesValid($createModel);

    if (!$validity->success) {
      return new JsonResponse($validity, Response::HTTP_BAD_REQUEST);
    }

    //Save round times
    $response = $this->roundTimeService->SaveRoundTimes($draft, $createModel);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }

  private function buildDepthChartPositionModel(InputBag $payload): DepthChartPositionCreateModel
  {
    $createPositionsModel = new DepthChartPositionCreateModel();
    $createPositionsModel->depthChartEnabled = true;

    $depthChartPositionJson = $payload->all()['depthChartPositions'] ?? [];
    $display_order = 0;

    foreach (is_array($depthChartPositionJson) ? $depthChartPositionJson : [] as $depthChartPositionRequest) {
      if (!is_array($depthChartPositionRequest)) {
        continue;
      }

      $depthChartPosition = new DepthChartPosition();
      $depthChartPosition->draft_id = null;
      $depthChartPosition->position = $depthChartPositionRequest['position'] ?? null;
      $depthChartPosition->slots = (int) ($depthChartPositionRequest['slots'] ?? 0);
      $depthChartPosition->display_order = $display_order++;

      $createPositionsModel->positions[] = $depthChartPosition;
    }

    return $createPositionsModel;
  }
}
