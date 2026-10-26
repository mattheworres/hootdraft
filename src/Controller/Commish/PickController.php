<?php

namespace HootDraft\Controller\Commish;

use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Services\PickService;
use HootDraft\Domain\Validators\PickValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Role (ROLE_COMMISH) comes from security.yaml; ownership of the draft from the commishEditableDraft check. */
class PickController
{
  private const EDIT = ['commishEditableDraft', 'draftInProgress'];

  public function __construct(
    private DraftRepository $drafts,
    private PickRepository $picks,
    private PickService $service,
    private PickValidator $validator,
  ) {
  }

  #[Route('/commish/draft/{draft_id}/pick/current', name: 'commish_pick_get_current', methods: ['GET'], defaults: ['_checks' => self::EDIT])]
  public function getCurrent(string $draft_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    $response = $this->service->GetCurrentPick($draft);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/pick/{player_id}', name: 'commish_pick_add', methods: ['POST'], defaults: ['_checks' => self::EDIT])]
  public function add(Request $request, string $draft_id, string $player_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    try {
      $pick = $this->picks->Load((int) $player_id);

      $this->fillPick($request, $pick);
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to add pick #" . (int) $player_id;

      return new JsonResponse($response, $response->responseType());
    }

    $validity = $this->validator->IsPickValidForAdd($draft, $pick);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->service->AddPick($draft, $pick);

    return new JsonResponse($response, $response->responseType(Response::HTTP_CREATED));
  }

  #[Route('/commish/draft/{draft_id}/pick/{player_id}', name: 'commish_pick_update', methods: ['PUT'], defaults: ['_checks' => self::EDIT])]
  public function update(Request $request, string $draft_id, string $player_id): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);

    try {
      $pick = $this->picks->Load((int) $player_id);

      $this->fillPick($request, $pick);
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $response->errors[] = "Unable to edit pick #" . (int) $player_id;

      return new JsonResponse($response, $response->responseType());
    }

    $validity = $this->validator->IsPickValidForUpdate($draft, $pick);

    if (!$validity->success) {
      return new JsonResponse($validity, $validity->responseType());
    }

    $response = $this->service->UpdatePick($draft, $pick);

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/picks/alreadyDrafted', name: 'commish_picks_already_drafted', methods: ['GET'], defaults: ['_checks' => self::EDIT])]
  public function alreadyDrafted(Request $request, string $draft_id): JsonResponse
  {
    $first_name = (string) $request->query->get('first_name');
    $last_name = (string) $request->query->get('last_name');

    $response = $this->service->AlreadyDrafted((int) $draft_id, $first_name, $last_name);

    return new JsonResponse($response, $response->responseType());
  }

  //This will also be used as the "Create" method to populate a list of valid rounds for the view to use
  #[Route('/commish/draft/{draft_id}/picks/lastFive', name: 'commish_picks_last_five', methods: ['GET'], defaults: ['_checks' => self::EDIT])]
  public function getLast5(string $draft_id): JsonResponse
  {
    $response = new HootDraftResponse();

    try {
      $draft = $this->drafts->Load((int) $draft_id);

      $response->draft_rounds = $draft->draft_rounds;
      $response->last_5_picks = $this->picks->LoadLastPicks((int) $draft_id, 5);
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load last 5 picks.";
    }

    return new JsonResponse($response, $response->responseType());
  }

  #[Route('/commish/draft/{draft_id}/round/{draft_round}/picks', name: 'commish_picks_by_round', methods: ['GET'], defaults: ['_checks' => self::EDIT])]
  public function getByRound(string $draft_id, string $draft_round): JsonResponse
  {
    $draft = $this->drafts->Load((int) $draft_id);
    $round = (int) $draft_round;
    $response = new HootDraftResponse();

    try {
      $response->round = $round;
      $response->round_picks = $this->picks->LoadRoundPicks($draft, $round, false, true);
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load round #$round's picks";
    }

    return new JsonResponse($response, $response->responseType());
  }

  /** The player the commissioner entered goes onto the pick before it is validated. */
  private function fillPick(Request $request, Pick $pick): void
  {
    $pick->first_name = $request->request->get('first_name');
    $pick->last_name = $request->request->get('last_name');
    $pick->team = $request->request->get('team');
    $pick->position = $request->request->get('position');
  }
}
