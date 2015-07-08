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

  /*
  * This method is only to be used internally or when the user has been verified as owner of the draft (or is admin)
  * (in other words, don't call this then return the result as JSON!)
  */
  public function LoadByRound($draft_id, $round) {
    /*$timer = new RoundTime();

    $draft_stmt = $this->app['db']->prepare("SELECT * FROM round_times
    WHERE draft_id = ? LIMIT 1");*/
    //TODO: Implement. Will need to 
  }
}