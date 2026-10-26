<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;

// AddPick, UpdatePick and AlreadyDrafted arrive with the commissioner pick routes in 4.5 (AddPick also needs the
// stats repository from 4.6 when it completes a draft). See old-api for the originals.
class PickService {
  public function __construct(
    private PickRepository $pickRepository,
    private DraftRepository $draftRepository,
    private DraftDataRepository $draftData,
    private RoundTimeService $roundTimeService,
  ) {
  }

  public function GetAll(Draft $draft) {
    $response = new HootDraftResponse();

    try {
      $response->allPicks = $this->pickRepository->LoadAll($draft);
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $message = $e->getMessage();
      $response->errors[] = "Unable to load picks: $message";
    }

    return $response;
  }

  public function GetCurrentPick(Draft $draft, $include_data = true) {
    $response = new HootDraftResponse();

    try {
      $response->pick = $this->pickRepository->GetCurrentPick($draft);

      if ($include_data) {
        $response->teams = $this->draftData->GetTeams($draft->draft_sport);
        $response->historical_teams = $this->draftData->GetHistoricalTeams($draft->draft_sport);
        $response->positions = $this->draftData->GetPositions($draft->draft_sport);
        $response->last_5_picks = $this->pickRepository->LoadLastPicks($draft->draft_id, 5);
        $response->next_5_picks = $this->pickRepository->LoadNextPicks($draft->draft_id, $draft->draft_current_pick, 5);
      }

      $response->success = true;
    } catch (\Exception $e) {
      //Rather than unset all of the 1-5 properties from the try above, just grab a new Response object:
      $response = new HootDraftResponse(false, array());

      $message = $e->getMessage();
      $response->errors[] = "Unable to load current pick: $message";
    }

    return $response;
  }

  public function UpdatePickDepthChart(Draft $draft, Pick $pick) {
    $response = new HootDraftResponse(true, array());

    try {
      //Purposefully NOT updating the draft counter. This is tied to a public-ish action.
      $pick = $this->pickRepository->UpdatePickDepthChart($pick);

      $response->pick = $pick;
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $errorMessage = $e->getMessage();
      $response->errors[] = "Unable to update pick depth chart: $errorMessage";
    }

    return $response;
  }

  public function LoadUpdatedData($draft_id, $pick_counter) {
    $response = new HootDraftResponse();

    try {
      $draft = $this->draftRepository->Load($draft_id);

      if ($pick_counter < $draft->draft_counter) {
        $response->updated_picks = $this->pickRepository->LoadUpdatedPicks($draft_id, $pick_counter);
        $response->current_pick = $this->pickRepository->GetCurrentPick($draft);
        $response->previous_pick = $this->pickRepository->GetPreviousPick($draft);

        $timer_response = $this->roundTimeService->GetCurrentPickTimeRemaining($draft);

        if (!$timer_response->success) {
          throw new \Exception(implode(" ", $timer_response->errors));
        }

        $response->timer_enabled = $timer_response->timer_enabled;
        $response->seconds_remaining = $timer_response->timer_enabled ? $timer_response->seconds_remaining : NULL;
      }

      $response->draft_counter = $draft->draft_counter;
      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $errorMessage = $e->getMessage();
      $response->errors[] = "Unable to get updated picks: $errorMessage";
    }

    return $response;
  }
}
