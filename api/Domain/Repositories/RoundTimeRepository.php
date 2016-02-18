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

  /*public function GetPublicTimers($draftId) {
    //TODO: Implement
  }*/

  public function GetDraftTimers(Draft $draft) {
    $draftId = (int)$draft->draft_id;

    $timerStmt = $this->app['db']->prepare("SELECT * FROM round_times
    WHERE draft_id = ? ORDER BY draft_round ASC");

    $timerStmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\RoundTime');
    $timerStmt->bindParam(1, $draftId);

    if(!$timerStmt->execute()) {
      throw new \Exception("Unable to load round times.");
    }

    $timers = array();

    while($timer = $timerStmt->fetch()) {
      $timers[] = $timer;
    }

    $isStaticTime = false;

    if(count($timers) == 1 && $timers[0]->is_static_time) {
      $isStaticTime = true;
    }

    if(empty($timers) || count($timers) != $draft->draft_rounds) {
      $timers = $this->_CoalesceDraftTimers($draft, $timers, $isStaticTime);
    }

    return $timers;
  }

  private function _CoalesceDraftTimers(Draft $draft, $existing_timers, $isStaticTime) {
    $coalescedTimers = array();

    for($i = 0; $i < $draft->draft_rounds; $i++) {
      if($isStaticTime && $i == 0) {
        $timer = $existing_timers[$i];
        $timer->draft_round = $i + 1;
        $coalescedTimers[] = $timer;
        continue;
      }

      if(array_key_exists($i, $existing_timers)) {
        $coalescedTimers[] = $existing_timers[$i];
      } else {
        $newTimer = new RoundTime();
        $newTimer->draft_id = $draft->draft_id;
        $newTimer->is_static_time = false;
        $newTimer->draft_round = $i + 1;
        $newTimer->round_time_seconds = 0;
        $coalescedTimers[] = $newTimer;
      }
    }

    return $coalescedTimers;
  }

  public function Save(RoundTimeCreateModel $roundTimeCreateModel) {
    $insertTimeStmt = $this->app['db']->prepare("INSERT INTO round_times 
      (draft_id, is_static_time, draft_round, round_time_seconds)
      VALUES
      (:draft_id, :is_static_time, :draft_round, :round_time_seconds)");

    $newRoundTimes = array();

    foreach ($roundTimeCreateModel->roundTimes as $roundTime) {
      $insertTimeStmt->bindValue(":draft_id", $roundTime->draft_id);
      $insertTimeStmt->bindValue(":is_static_time", $roundTime->is_static_time);
      $insertTimeStmt->bindValue(":draft_round", $roundTime->draft_round);
      $insertTimeStmt->bindValue(":round_time_seconds", $roundTime->round_time_seconds);

      if (!$insertTimeStmt->execute()) {
        throw new \Exception("Unable to save round times for $roundTime->draft_id");
      }

      $roundTime->round_time_id = (int)$this->app['db']->lastInsertId();
      $newRoundTimes[] = $roundTime;
    }

    return $newRoundTimes;
  }

  public function DeleteAll($draftId) {
    $deleteRoundTime = $this->app['db']->prepare("DELETE FROM round_times WHERE draft_id = ?");
    $deleteRoundTime->bindParam(1, $draftId);

    if(!$deleteRoundTime->execute()) {
      throw new \Exception("Unable to delete round times: " . $this->app['db']->errorInfo());
    }

    return;
  }

  public function LoadByRound(Draft $draft) {
    $roundTime = new RoundTime();

    $staticRoundTimeStmt = $this->app['db']->prepare("SELECT * FROM round_times WHERE draft_id = ? AND is_static_time = 1 LIMIT 1");
    $staticRoundTimeStmt->setFetchMode(\PDO::FETCH_INTO, $roundTime);
    $staticRoundTimeStmt->bindParam(1, $draft->draft_id);

    if(!$staticRoundTimeStmt->execute()) {
      throw new \Exception("Unable to get static round time.");
    }

    if($staticRoundTimeStmt->rowCount() == 1) {
      $staticRoundTimeStmt->fetch();

      return $roundTime;
    }

    $roundTimeStmt = $this->app['db']->prepare("SELECT * FROM round_times WHERE draft_id = ? AND draft_round = ? LIMIT 1");
    $roundTimeStmt->setFetchMode(\PDO::FETCH_INTO, $roundTime);
    $roundTimeStmt->bindParam(1, $draft->draft_id);
    $roundTimeStmt->bindParam(2, $draft->draft_current_round);

    if (!$roundTimeStmt->execute()) {
      throw new \Exception("Unable to load round time:" . $this->app['db']->errorInfo());
    }

    if($roundTimeStmt->rowCount() == 0) {
      return null;
    }

    if (!$roundTimeStmt->fetch()) {
      throw new \Exception("Unable to load round time:" . $this->app['db']->errorInfo());
    }

    return $roundTime;
  }
}