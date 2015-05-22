<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Draft;

class ManagerRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetPublicManagers($draft_id) {
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
}