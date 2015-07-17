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

  public function GetDraftTimers($draft_id) {
    $draft_id = (int)$draft_id;

    $timer_stmt = $this->app['db']->prepare("SELECT * FROM round_times
    WHERE draft_id = ?");

    $timer_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\RoundTime');
    $timer_stmt->bindParam(1, $draft_id);

    if(!$timer_stmt->execute()) {
      throw new \Exception("Unable to load round times.");
    }

    $timers = array();

    while($timer = $timer_stmt->fetch()) {
      $timers[] = $timer;
    }

    return $timers;
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