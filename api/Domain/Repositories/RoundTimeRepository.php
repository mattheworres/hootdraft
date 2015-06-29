<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\RoundTime;

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

  public function SaveDraftTimers($draft_id, $timers) {
    //TODO: Implement
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