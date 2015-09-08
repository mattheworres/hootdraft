<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetPublicTimers($draft_id) {
    //TODO: Implement
  }

  public function GetDraftTimers(Draft $draft) {
    $draft_id = (int)$draft->draft_id;

    $timer_stmt = $this->app['db']->prepare("SELECT * FROM round_times
    WHERE draft_id = ? ORDER BY draft_round ASC");

    $timer_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\RoundTime');
    $timer_stmt->bindParam(1, $draft_id);

    if(!$timer_stmt->execute()) {
      throw new \Exception("Unable to load round times.");
    }

    $timers = array();

    while($timer = $timer_stmt->fetch()) {
      $timers[] = $timer;
    }

    $is_static_time = false;

    if(count($timers) == 1 && $timers[0]->is_static_time) {
      $is_static_time = true;
    }

    if(empty($timers) || count($timers) != $draft->draft_rounds) {
      $timers = $this->_CoalesceDraftTimers($draft, $timers, $is_static_time);
    }

    return $timers;
  }

  private function _CoalesceDraftTimers(Draft $draft, $existing_timers, $is_static_time) {
    $coalescedTimers = array();

    for($i = 0; $i <= $draft->draft_rounds; $i++) {
      if($is_static_time && $i == 0) {
        $timer = $existing_timers[$i];
        $timer->draft_round = $i + 1;
        $coalescedTimers[] = $timer;
        continue;
      }

      if(array_key_exists($i, $existing_timers)) {
        $coalescedTimers[] = $existing_timers[$i];
      } else {
        $new_timer = new RoundTime();
        $new_timer->draft_id = $draft->draft_id;
        $new_timer->is_static_time = false;
        $new_timer->draft_round = $i + 1;
        $new_timer->round_time_seconds = 0;
        $coalescedTimers[] = $new_timer;
      }
    }

    return $coalescedTimers;
  }

  public function Save(RoundTimeCreateModel $roundTimeCreateModel) {
    $insert_time_stmt = $this->app['db']->prepare("INSERT INTO round_times 
      (draft_id, is_static_time, draft_round, round_time_seconds)
      VALUES
      (:draft_id, :is_static_time, :draft_round, :round_time_seconds)");

    $newRoundTimes = array();

    foreach ($roundTimeCreateModel->roundTimes as $roundTime) {
      $insert_time_stmt->bindValue(":draft_id", $roundTime->draft_id);
      $insert_time_stmt->bindValue(":is_static_time", $roundTime->is_static_time);
      $insert_time_stmt->bindValue(":draft_round", $roundTime->draft_round);
      $insert_time_stmt->bindValue(":round_time_seconds", $roundTime->round_time_seconds);

      if (!$insert_time_stmt->execute()) {
        throw new \Exception("Unable to save round times for $roundTime->draft_id");
      }

      $roundTime->round_time_id = (int)$this->app['db']->lastInsertId();
      $newRoundTimes[] = $roundTime;
    }

    return $newRoundTimes;
  }

  public function DeleteAll($draft_id) {
    $delete_round_times_stmt = $this->app['db']->prepare("DELETE FROM round_times WHERE draft_id = ?");
    $delete_round_times_stmt->bindParam(1, $draft_id);

    if(!$delete_round_times_stmt->execute()) {
      throw new \Exception("Unable to delete round times: " . $this->app['db']->errorInfo());
    }

    return;
  }

  public function LoadByRound(Draft $draft) {
    $round_time = new RoundTime();

    $static_round_time_stmt = $this->app['db']->prepare("SELECT * FROM round_times WHERE draft_id = ? AND is_static_time = 1 LIMIT 1");
    $static_round_time_stmt->setFetchMode(\PDO::FETCH_INTO, $round_time);
    $static_round_time_stmt->bindParam(1, $draft->draft_id);

    if(!$static_round_time_stmt->execute()) {
      throw new \Exception("Unable to get static round time.");
    }

    if($static_round_time_stmt->rowCount() == 1) {
      $static_round_time_stmt->fetch();

      return $round_time;
    }

    $round_time_stmt = $this->app['db']->prepare("SELECT * FROM round_times WHERE draft_id = ? AND draft_round = ? LIMIT 1");
    $round_time_stmt->setFetchMode(\PDO::FETCH_INTO, $round_time);
    $round_time_stmt->bindParam(1, $draft->draft_id);
    $round_time_stmt->bindParam(2, $draft->draft_current_round);

    if (!$round_time_stmt->execute()) {
      throw new \Exception("Unable to load round time:" . $this->app['db']->errorInfo());
    }

    if($round_time_stmt->rowCount() == 0) {
      return null;
    }

    if (!$round_time_stmt->fetch()) {
      throw new \Exception("Unable to load round time:" . $this->app['db']->errorInfo());
    }

    return $round_time;
  }
}