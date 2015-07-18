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
    $load_stmt->bindParam(1, $id);

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

  public function GetNumberOfCurrentManagers($draft_id) {
    $manager_stmt = $this->app['db']->prepare("SELECT COUNT(manager_id) FROM managers WHERE draft_id = ?");
    $manager_stmt->bindParam(1, $draft_id);

    if(!$manager_stmt->execute()) {
      throw new \Exception("Unable to get number of managers for draft #$draft_id");
    }

    return (int)$manager_stmt->fetchColumn(0);
  }

  public function NameIsUnique($name, $draft_id, $id = null) {
    if(!empty($id)) {
      $name_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE manager_name LIKE ? AND draft_id = ? AND manager_id <> ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $draft_id);
      $name_stmt->bindParam(3, $id);
    } else {
      $name_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE manager_name LIKE ? AND draft_id = ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $draft_id);
    }

    if(!$name_stmt->execute()) {
      throw new \Exception("Manager name '$name' is invalid");
    }

    return $name_stmt->rowCount() == 0;
  }

  public function ManagerExists($id, $draft_id) {
    $exists_stmt = $this->app['db']->prepare("SELECT COUNT(manager_id) FROM managers WHERE manager_id = ? AND draft_id = ?");
    $exists_stmt->bindParam(1, $id);
    $exists_stmt->bindParam(2, $draft_id);

    if(!$exists_stmt->execute()) {
      throw new \Exception("Manager ID $id is invalid");
    }

    return $exists_stmt->fetchColumn(0) == 1;
  }

  public function Create(Manager $manager) {
    $save_stmt = $this->app['db']->prepare("INSERT INTO managers (manager_id, draft_id, manager_name, draft_order) VALUES (NULL, ?, ?, ?)");
    $save_stmt->bindParam(1, $manager->draft_id);
    $save_stmt->bindParam(2, $manager->manager_name);
    $save_stmt->bindParam(3, $new_draft_order);

    $new_draft_order = $this->_GetLowestDraftorder($manager->draft_id) + 1;

    if (!$save_stmt->execute()) {
      throw new \Exception("Unable to create new manager: " . $this->app['db']->errorInfo());
    }

    $manager->manager_id = (int) $this->app['db']->lastInsertId();

    return $manager;
  }

  public function CreateMany($draft_id, $managersArray) {
    $save_stmt = $this->app['db']->prepare("INSERT INTO managers (manager_id, draft_id, manager_name, draft_order) 
      VALUES 
      (NULL, :draft_id, :manager_name, :draft_order)");
    $newDraftOrder = $this->_GetLowestDraftorder($draft_id) + 1;
    $newManagers = array();

    foreach($managersArray as $newManager) {
      $save_stmt->bindValue(':draft_id', $newManager->draft_id);
      $save_stmt->bindValue(':manager_name', $newManager->manager_name);
      $save_stmt->bindValue(':draft_order', $newDraftOrder++);

      if (!$save_stmt->execute()) {
        throw new \Exception("Unable to save managers for $draft_id");
      }

      $newManager->manager_id = (int)$this->app['db']->lastInsertId();
      $newManagers[] = $newManager;
    }

    return $newManagers;
  }

  public function Update(Manager $manager) {
    $update_stmt = $this->app['db']->prepare("UPDATE managers SET manager_name = ? WHERE manager_id = ?");
    $update_stmt->bindParam(1, $manager->manager_name);
    $update_stmt->bindParam(2, $manager->manager_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update manager #$manager->manager_id");
    }

    return $manager;
  }

  public function ReorderManagers($managersIdArray) {
    $reorder_stmt = $this->app['db']->prepare("UPDATE managers SET draft_order = :draft_order WHERE manager_id = :manager_id");

    $newDraftOrder = 1;

    foreach($managersIdArray as $manager_id) {
      $reorder_stmt->bindValue(':draft_order', $newDraftOrder++);
      $reorder_stmt->bindValue(':manager_id', $manager_id);

      if(!$reorder_stmt->execute()) {
        throw new \Exception("Unable to update manager order for manager #$manager_id");
      }
    }

    return;
  }

  public function DeleteManager($manager_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM managers WHERE manager_id = ?");
    $delete_stmt->bindParam(1, $manager_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete manager $manager_id.");
    }

    return;
  }

  public function DeleteAllManagers($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM managers WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete managers for draft $draft_id.");
    }

    return;
  }

  /**
   * In order to get the lowest current draft number for this draft.
   * @return int Lowest draft order for the given draft
   */
  private function _GetLowestDraftorder($draft_id) {
    $stmt = $this->app['db']->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
    $stmt->bindParam(1, $draft_id);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get lowest manager draft order.");
    }

    //If there are no managers, the lowest order is 0 since we'll be adding 1 to it.
    if ($stmt->rowCount() == 0) {
      return 0;
    }

    if (!$row = $stmt->fetch()) {
      throw new \Exception("Unable to get lowest manager draft order.");
    }

    return (int)$row['draft_order'];
  }
}