<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  /*A replacement for the "hasBeenSelected" from 1.x - does in-memory logic to determine if pick is a selection yet*/
  public function PickHasBeenSelected(Pick $pick) {
    $hasTime = is_null($pick->pick_time);
    $hasDuration = is_null($pick->pick_duration);
    $selected = (!$hasTime && !$hasDuration);

    return $selected ? 1 : 0;
  }

  public function GetCurrentPick(Draft $draft, $include_data = true) {
    $response = new PhpDraftResponse();

    try {
      $response->pick = $this->app['phpdraft.PickRepository']->GetCurrentPick($draft);

      if($include_data) {
        $response->teams = $this->app['phpdraft.DraftDataRepository']->GetTeams($draft->draft_sport);
        $response->positions = $this->app['phpdraft.DraftDataRepository']->GetPositions($draft->draft_sport, $draft->nfl_extended);
        $response->last_5_picks = $this->app['phpdraft.PickRepository']->LoadLastPicks($draft->draft_id, 5);
        $response->next_5_picks = $this->app['phpdraft.PickRepository']->LoadNextPicks($draft->draft_id, $draft->draft_current_pick, 5);
      }

      $response->success = true;
    } catch(\Exception $e) {
      //Rather than unset all of the 1-5 properties from the try above, just grab a new Response object:
      $response = new PhpDraftResponse(false, array());

      $message = $e->getMessage();
      $response->errors[] = "Unable to load current pick: $message";
    }

    return $response;
  }

  public function AddPick(Draft $draft, Pick $pick) {
    $response = new PhpDraftResponse();

    try {
      //Set pick time and duration
      $pick = $this->_CalculatePickTimeAndDuration($draft, $pick);
      //Increment the draft counter
      $draft->draft_counter = $this->app['phpdraft.DraftRepository']->IncrementDraftCounter($draft);
      //Set the value on the pick
      $pick->player_counter = $draft->draft_counter;
      //Save the pick
      $pick = $this->app['phpdraft.PickRepository']->AddPick($pick);
      //Get the next pick
      $next_pick = $this->app['phpdraft.PickRepository']->GetNextPick($draft);
      //Move draft forward
      $draft = $this->app['phpdraft.DraftRepository']->MoveDraftForward($draft, $next_pick);

      $response->draft_is_complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);

      if($response->draft_is_complete) {
        $response->draft_statistics = $this->app['phpdraft.DraftStatsRepository']->CalculateDraftStatistics($draft);
      }

      $response->pick = $pick;
      $response->next_pick = $next_pick;
      $response->last_5_picks = $this->app['phpdraft.PickRepository']->LoadLastPicks($draft->draft_id, 5);
      $response->next_5_picks = $this->app['phpdraft.PickRepository']->LoadNextPicks($draft->draft_id, $draft->draft_current_pick, 5);

      $response->success = true;
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());

      $errorMessage = $e->getMessage();

      $response->errors[] = "Unable to add pick: $errorMessage";
    }

    return $response;
  }

  public function UpdatePick(Draft $draft, Pick $pick) {
    $response = new PhpDraftResponse();

    try {
      //Increment the draft counter
      $draft->draft_counter = $this->app['phpdraft.DraftRepository']->IncrementDraftCounter($draft);
      //Set the value on the pick
      $pick->player_counter = $draft->draft_counter;
      //Save the pick
      $pick = $this->app['phpdraft.PickRepository']->AddPick($pick);

      $response->pick = $pick;
      $response->success = true;
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());

      $errorMessage = $e->getMessage();

      $response->errors[] = "Unable to add pick: $errorMessage";
    }

    return $response;
  }

  public function AlreadyDrafted($draft_id, $first_name, $last_name) {
    $response = new PhpDraftResponse();

    try {
      $response->matches = $this->app['phpdraft.PickRepository']->SearchAlreadyDrafted($draft_id, $first_name, $last_name);
      $response->possibleMatchExists = count($response->matches) > 0;
      $response->success = true;
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $errorMessage = $e->getMessage();
      $response->errors[] = "Unable to check for already drafted: $errorMessage";
    }

    return $response;
  }

  private function _CalculatePickTimeAndDuration(Draft $draft, Pick $pick) {
    //Get the previous pick
    $previous_pick = $this->app['phpdraft.PickRepository']->GetPreviousPick($draft);

    //Get the current time
    $now_utc = new \DateTime(null, new \DateTimeZone("UTC"));
    $now_utc_timestamp = $now_utc->getTimestamp();
    $pick->pick_time = $now_utc->format('Y-m-d H:i:s');

    $this->app['monolog']->addDebug("Pick time now: $pick->pick_time");

    //Calculate the pick duration
    if ($pick->player_pick == 1 || $previous_pick == null)
      $start_time = new \DateTime($draft->draft_start_time, new \DateTimeZone("UTC"));
    else
      $start_time = new \DateTime($previous_pick->pick_time, new \DateTimeZone("UTC"));

    $start_time_timestamp = $start_time->getTimestamp();

    $alloted_time = $now_utc_timestamp - $start_time_timestamp;

    $pick->pick_duration = (int)$alloted_time;

    return $pick;
  }
}