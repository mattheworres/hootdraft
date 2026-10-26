<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\PickSearchRepository;

class PickService {
  public function __construct(
    private PickRepository $pickRepository,
    private DraftRepository $draftRepository,
    private DraftDataRepository $draftData,
    private RoundTimeService $roundTimeService,
    private PickSearchRepository $pickSearch,
    private DraftService $draftService,
    private \PDO $pdo,
  ) {
  }

  /*A replacement for the "hasBeenSelected" from 1.x - does in-memory logic to determine if pick is a selection yet*/
  public function PickHasBeenSelected(Pick $pick) {
    $hasTime = is_null($pick->pick_time);
    $hasDuration = is_null($pick->pick_duration);
    $selected = (!$hasTime && !$hasDuration);

    return $selected ? 1 : 0;
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

  public function AddPick(Draft $draft, Pick $pick) {
    $response = new HootDraftResponse();

    try {
      // The counter, the pick and the draft's position move together or not at all
      $this->pdo->beginTransaction();

      // Re-check against the DB under a row lock, so a double-submit can't make the same pick twice
      if ($this->draftRepository->LockCurrentPick($draft) != $pick->player_pick) {
        throw new \Exception("Pick #$pick->player_pick is no longer the current pick.");
      }

      //Set pick time and duration
      $pick = $this->_CalculatePickTimeAndDuration($draft, $pick);
      //Increment the draft counter
      $draft->draft_counter = $this->draftRepository->IncrementDraftCounter($draft);
      //Set the value on the pick
      $pick->player_counter = $draft->draft_counter;
      //Save the pick
      $pick = $this->pickRepository->AddPick($pick);
      //Get the next pick
      $next_pick = $this->pickRepository->GetNextPick($draft);
      //Move draft forward
      $draft = $this->draftRepository->MoveDraftForward($draft, $next_pick);

      $this->pdo->commit();
      $this->draftRepository->FlushPendingCacheResets();

      $response->draft_is_complete = $this->draftService->DraftComplete($draft);

      // TODO(4.6): when this pick completes the draft (draft_is_complete == 1), the old code also set
      // $response->draft_statistics from DraftStatsRepository::CalculateDraftStatistics. The AngularJS client
      // does not read it from this response (the stats page loads it from /draft/{id}/stats).

      $response->pick = $pick;
      $response->next_pick = $next_pick;
      $response->last_5_picks = $this->pickRepository->LoadLastPicks($draft->draft_id, 5);
      $response->next_5_picks = $this->pickRepository->LoadNextPicks($draft->draft_id, $draft->draft_current_pick, 5);

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();

      $response = new HootDraftResponse(false, array());

      $errorMessage = $e->getMessage();

      $response->errors[] = "Unable to add pick: $errorMessage";
    }

    return $response;
  }

  public function UpdatePick(Draft $draft, Pick $pick) {
    $response = new HootDraftResponse(true, array());

    try {
      //Increment the draft counter
      $draft->draft_counter = $this->draftRepository->IncrementDraftCounter($draft);
      //Set the value on the pick
      $pick->player_counter = $draft->draft_counter;
      //Save the pick
      $pick = $this->pickRepository->AddPick($pick);

      $response->pick = $pick;
      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();

      $response = new HootDraftResponse(false, array());

      $errorMessage = $e->getMessage();

      $response->errors[] = "Unable to add pick: $errorMessage";
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

  public function AlreadyDrafted($draft_id, $first_name, $last_name) {
    $response = new HootDraftResponse();

    try {
      $response->matches = $this->pickSearch->SearchAlreadyDrafted($draft_id, $first_name, $last_name);
      $response->possibleMatchExists = count($response->matches) > 0;
      $response->success = true;
    } catch (\Exception $e) {
      $response = new HootDraftResponse(false, array());
      $errorMessage = $e->getMessage();
      $response->errors[] = "Unable to check for already drafted: $errorMessage";
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

  private function _CalculatePickTimeAndDuration(Draft $draft, Pick $pick) {
    //Get the previous pick
    $previous_pick = $this->pickRepository->GetPreviousPick($draft);

    //Get the current time
    $now_utc = new \DateTime('now', new \DateTimeZone("UTC"));
    $now_utc_timestamp = $now_utc->getTimestamp();
    $pick->pick_time = $now_utc->format('Y-m-d H:i:s');

    //Calculate the pick duration
    if ($pick->player_pick == 1 || $previous_pick == null) {
      $start_time = new \DateTime((string) $draft->draft_start_time, new \DateTimeZone("UTC"));
    } else {
      $start_time = new \DateTime((string) $previous_pick->pick_time, new \DateTimeZone("UTC"));
    }

    $start_time_timestamp = $start_time->getTimestamp();

    $alloted_time = $now_utc_timestamp - $start_time_timestamp;

    $pick->pick_duration = (int)$alloted_time;

    return $pick;
  }

  private function rollBack() {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }

    $this->draftRepository->FlushPendingCacheResets();
  }
}
