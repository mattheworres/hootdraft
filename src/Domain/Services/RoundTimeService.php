<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\RoundTimeCreateModel;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;

class RoundTimeService {
  public function __construct(
    private RoundTimeRepository $roundTimeRepository,
    private PickRepository $pickRepository,
    private \PDO $pdo,
  ) {
  }

  public function SaveRoundTimes(Draft $draft, RoundTimeCreateModel $model) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      $this->roundTimeRepository->DeleteAll($draft->draft_id);
      $roundTimes = $this->roundTimeRepository->Save($model);

      $this->pdo->commit();

      $response->success = true;
      $response->roundTimes = $roundTimes;
    } catch (\Exception $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetCurrentPickTimeRemaining(Draft $draft) {
    $response = new HootDraftResponse();

    try {
      $last_pick = $this->pickRepository->GetPreviousPick($draft);
      $current_round_picktime = $this->roundTimeRepository->LoadByRound($draft);
    } catch (\Exception $e) {
      // The old code carried on after this and answered success with an undefined round time
      $response->success = false;
      $response->errors = array($e->getMessage());

      return $response;
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
    $last_pick_datetime = new \DateTime((string) $last_pick_time, new \DateTimeZone("UTC"));
    $last_pick_timestamp = $last_pick_datetime->getTimestamp();

    $timer_ends_at = $last_pick_timestamp + $current_round_picktime->round_time_seconds;

    //then subtract the NOW timestamp to get seconds left and return that for timer to count down.
    $now_utc = new \DateTime('now', new \DateTimeZone("UTC"));
    $right_now = $now_utc->getTimestamp();
    $seconds_remaining = $timer_ends_at - $right_now;

    //Return non-negative seconds figure, 0 meaning TIME IS UP!
    $response->seconds_remaining = max($seconds_remaining, 0);

    return $response;
  }
}
