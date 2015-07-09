<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Manager;

class ManagerRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function Load($id) {
    $manager = new Manager();

    $load_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE manager_id = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $manager);
    $load_stmt->bindParam(1, (int) $id);

    if (!$load_stmt->execute())
      throw new \Exception(sprintf('Manager "%s" does not exist.', $manager));

    if (!$load_stmt->fetch())
      throw new \Exception(sprintf('Manager "%s" does not exist.', $id));

    return $manager;
  }

  public function GetPublicManagers($draft_id) {
    $managers = array();

    $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order");
    $managers_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Manager');

    $managers_stmt->bindParam(1, $draft_id);

    if(!$managers_stmt->execute()) {
      throw new \Exception("Unable to load managers for draft #$draft_id");
    }

    while($manager = $managers_stmt->fetch()) {
      $managers[] = $manager;
    }

    return $managers;
  }

  public function GetManagersByDraftOrder($draft_id, $descending = false) {
    $managers = array();

    if($descending) {
      $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order DESC");
    } else {
      $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order");  
    }

    $managers_stmt->bindParam(1, $draft_id);

    $managers_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Manager');

    if(!$managers_stmt->execute()) {
      throw new \Exception("Unable to load managers for draft #$draft_id");
    }

    while($manager = $managers_stmt->fetch()) {
      $managers[] = $manager;
    }

    return $managers;
  }

  //Ensure a draft has the minimum number of managers - 2
  public function DraftHasManagers($draft_id) {
    $manager_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE draft_id = ?");
    $manager_stmt->bindParam(1, $draft_id);

    if(!$manager_stmt->execute()) {
      throw new \Exception("Draft id '%s' is invalid", $draft_id);
    }

    return $manager_stmt->rowCount() > 1;
  }
}