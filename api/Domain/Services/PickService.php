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
}