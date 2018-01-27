<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function SaveRoundTimes(Draft $draft, RoundTimeCreateModel $model) {
    $response = new PhpDraftResponse();

    try {
      $this->app['phpdraft.RoundTimeRepository']->DeleteAll($draft->draft_id);
      $roundTimes = $this->app['phpdraft.RoundTimeRepository']->Save($model);

      $response->success = true;
      $response->roundTimes = $roundTimes;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetCurrentPickTimeRemaining(Draft $draft) {
    $response = new PhpDraftResponse();

    try {
      $current_pick = $this->app['phpdraft.PickRepository']->GetCurrentPick($draft);
      $last_pick = $this->app['phpdraft.PickRepository']->GetPreviousPick($draft);
      $current_round_picktime = $this->app['phpdraft.RoundTimeRepository']->LoadByRound($draft);
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    $response->success = true;
    $response->timer_enabled = $current_round_picktime != null;

    if (!$response->timer_enabled) {
      return $response;
    }

    //Take last pick's picktime and add our timer seconds to it
    $last_pick_time = $last_pick != null
      ? $last_pick->pick_time
      : $draft->draft_start_time;

    //Because strtotime uses the server timezone, we can create a DateTime object, specify UTC, then get the Unix timestamp that way:
    $last_pick_datetime = new \DateTime($last_pick_time, new \DateTimeZone("UTC"));
    $last_pick_timestamp = $last_pick_datetime->getTimestamp();

    $timer_ends_at = $last_pick_timestamp + $current_round_picktime->round_time_seconds;

    //then subtract the NOW timestamp to get seconds left and return that for timer to count down.
    $now_utc = new \DateTime(null, new \DateTimeZone("UTC"));
    $right_now = $now_utc->getTimestamp();
    $seconds_remaining = $timer_ends_at - $right_now;

    //Return non-negative seconds figure, 0 meaning TIME IS UP!
    $response->seconds_remaining = max($seconds_remaining, 0);

    return $response;
  }
}