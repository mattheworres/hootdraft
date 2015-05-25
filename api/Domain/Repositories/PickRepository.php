<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;

class PickRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function LoadUpdatedPicks($draft_id, $pick_counter) {
    $picks = array();
    
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM players p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_counter > ? ORDER BY p.player_counter");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $pick_counter);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load updated picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }
}