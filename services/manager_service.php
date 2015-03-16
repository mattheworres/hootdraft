<?php

/**
 * Manager Service - an object service for the PHPDraft "manager" object.
 * 
 * Managers have many players (picks), and belong to a single draft.
 */
class manager_service {
  /**
   * Load a given manager
   * @global PDO $DBH
   * @param type $id
   * @return \manager_object
   * @throws Exception
   */
  public function loadManager($id = 0) {
    $manager = new manager_object();

    $id = (int) $id;

    if ($id == 0) {
      return $manager;
    }

    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT * FROM managers WHERE manager_id = ? LIMIT 1");
    $stmt->bindParam(1, $id);
    $stmt->setFetchMode(PDO::FETCH_INTO, $manager);

    if (!$stmt->execute()) {
      throw new Exception("Unable to load manager.");
    }

    if (!$stmt->fetch()) {
      throw new Exception("Unable to load manager.");
    }

    return $manager;
  }

  /**
   * Check the validity of parent manager object and return array of error descriptions if invalid.
   *
   * @param $manager
   * @return array/string errors
   */
  public function getValidity($manager) {
    $email_regex = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";

    $errors = array();

    if (!isset($manager->manager_name) || strlen($manager->manager_name) == 0)
      $errors[] = "Manager name is empty.";


    if (isset($manager->manager_email) && strlen($manager->manager_email) > 0) {
      $is_valid_email = (bool) preg_match($email_regex, $manager->manager_email);
      if (!$is_valid_email)
        $errors[] = "Manager email is not in the correct format";
    }

    global $DBH; /* @var $DBH PDO */

    $has_draft_stmt = $DBH->prepare("SELECT COUNT(draft_id) as count FROM draft WHERE draft_id = ?");
    $has_draft_stmt->bindParam(1, $manager->draft_id);

    if (!$has_draft_stmt->execute())
      $errors[] = $manager->draft_name . " unable to be added";

    if (!$row = $has_draft_stmt->fetch())
      $errors[] = $manager->draft_name . " unable to be added";

    if ((int) $row['count'] == 0)
      $errors[] = "Manager's draft doesn't exist.";

    return $errors;
  }

  /**
   * Move the given manager up in their draft order
   * @return manager_object $manager on success, exception thrown otherwise
   */
  public function moveManagerUp($manager) {
    global $DBH; /* @var $DBH PDO */

    if ($manager->draft_order == 0) {
      throw new Exception("Invalid manager draft order.");
    }

    $old_place = $manager->draft_order;

    if ($old_place == 1) {
      return $manager;
    }

    $new_place = $old_place - 1;

    $swap_mgr_stmt = $DBH->prepare("SELECT manager_id FROM managers WHERE draft_id = ? AND manager_id != ? AND draft_order = ?");
    $swap_mgr_stmt->bindParam(1, $manager->draft_id);
    $swap_mgr_stmt->bindParam(2, $manager->manager_id);
    $swap_mgr_stmt->bindParam(3, $new_place);

    if (!$swap_mgr_stmt->execute()) {
      throw new Exception("Unable to move manager.");
    }

    if (!$swap_manager_row = $swap_mgr_stmt->fetch()) {
      throw new Exception("Unable to move manager.");
    }

    $swap_manager_id = (int) $swap_manager_row['manager_id'];

    $swap_stmt = $DBH->prepare("UPDATE managers SET draft_order = ? WHERE draft_id = ? AND manager_id = ?");
    $swap_stmt->bindParam(1, $draft_order);
    $swap_stmt->bindParam(2, $manager->draft_id);
    $swap_stmt->bindParam(3, $manager_id);

    $draft_order = $new_place;
    $manager_id = $manager->manager_id;

    $swap_1_success = $swap_stmt->execute();

    $draft_order = $old_place;
    $manager_id = $swap_manager_id;

    $swap_2_success = $swap_stmt->execute();

    if (!$swap_1_success || !$swap_2_success) {
      throw new Exception("Unable to move manager.");
    }

    return $manager;
  }

  /**
   * Move the given manager down in their draft order
   * @return manager_object $manager on success, exception thrown otherwise
   */
  public function moveManagerDown($manager) {
    global $DBH; /* @var $DBH PDO */

    if ($manager->draft_order == 0) {
      throw new Exception("Invalid manager draft order.");
    }

    $old_place = $manager->draft_order;

    $lowest_order_stmt = $DBH->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
    $lowest_order_stmt->bindParam(1, $manager->draft_id);

    if (!$lowest_order_stmt->execute()) {
      throw new Exception("Unable to move manager.");
    }

    if (!$lowest_order_row = $lowest_order_stmt->fetch()) {
      throw new Exception("Unable to move manager.");
    }

    $lowest_order = (int) $lowest_order_row['draft_order'];

    if ($old_place == $lowest_order) {
      return $manager;
    }

    $new_place = $old_place + 1;

    $swap_mgr_stmt = $DBH->prepare("SELECT draft_order, manager_id FROM managers WHERE draft_id = ? AND manager_id != ? AND draft_order = ?");
    $swap_mgr_stmt->bindParam(1, $manager->draft_id);
    $swap_mgr_stmt->bindParam(2, $manager->manager_id);
    $swap_mgr_stmt->bindParam(3, $new_place);

    if (!$swap_mgr_stmt->execute()) {
      throw new Exception("Unable to move manager.");
    }

    if (!$swap_mgr_row = $swap_mgr_stmt->fetch()) {
      throw new Exception("Unable to move manager.");
    }

    $swap_manager_id = (int) $swap_mgr_row['manager_id'];

    $swap_stmt = $DBH->prepare("UPDATE managers SET draft_order = ? WHERE draft_id = ? AND manager_id = ?");
    $swap_stmt->bindParam(1, $draft_order);
    $swap_stmt->bindParam(2, $manager->draft_id);
    $swap_stmt->bindParam(3, $manager_id);

    $draft_order = $new_place;
    $manager_id = $manager->manager_id;

    $swap_1_success = $swap_stmt->execute();

    $draft_order = $old_place;
    $manager_id = $swap_manager_id;

    $swap_2_success = $swap_stmt->execute();

    if (!$swap_1_success || !$swap_2_success) {
      throw new Exception("Unable to move manager.");
    }

    return $manager;
  }

  /**
   * Saves or updates the manager object
   *
   * @param $manager
   * @throws Exception
   * @return manager_object $manager on success, exception thrown otherwise
   */
  public function saveManager($manager) {
    global $DBH; /* @var $DBH PDO */
    if ($manager->manager_id > 0) {
      $update_stmt = $DBH->prepare("UPDATE managers SET manager_name = ?, manager_email = ? WHERE manager_id = ? AND draft_id = ?");
      $update_stmt->bindParam(1, $manager->manager_name);
      $update_stmt->bindParam(2, $manager->manager_email);
      $update_stmt->bindParam(3, $manager->manager_id);
      $update_stmt->bindParam(4, $manager->draft_id);

      if (!$update_stmt->execute()) {
        throw new Exception("Unable to update manager.");
      }

      return $manager;
    } else {
      $save_stmt = $DBH->prepare("INSERT INTO managers (manager_id, draft_id, manager_name, manager_email, draft_order) VALUES (?, ?, ?, ?, ?)");
      $save_stmt->bindParam(1, $manager->manager_id);
      $save_stmt->bindParam(2, $manager->draft_id);
      $save_stmt->bindParam(3, $manager->manager_name);
      $save_stmt->bindParam(4, $manager->manager_email);
      $save_stmt->bindParam(5, $new_draft_order);

      try {
        $new_draft_order = (int) ($this->getLowestDraftorder($manager->draft_id) + 1);
      } catch (Exception $e) {
        throw new Exception("Unable to save manager: " . $e->getMessage());
      }


      if (!$save_stmt->execute()) {
        throw new Exception("Unable to save manager.");
      }

      $manager->manager_id = (int) $DBH->lastInsertId();

      return $manager;
    }
  }

  /**
   * In order to get the lowest current draft number for this draft.
   * Keeping this in manager service because it's only called during manager save, and hits managers table.
   * @return int Lowest draft order for the given draft 
   */
  public function getLowestDraftorder($draft_id) {
    global $DBH; /* @var $DBH PDO */
    $stmt = $DBH->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
    $stmt->bindParam(1, $draft_id);

    if (!$stmt->execute()) {
      throw new Exception("Unable to get lowest draft order. A");
    }

    if ($stmt->rowCount() == 0) {
      return 0;
    }

    if (!$row = $stmt->fetch()) {
      throw new Exception("Unable to get lowest draft order. B");
    }

    return (int) $row['draft_order'];
  }

  /**
   * Delete this manager, ensuring draft is not in
   * progress or completed (use static deleteManagersByDraft() instead
   * if deleting a draft)
   * @return boolean on success, exception thrown otherwise
   */
  public function deleteManager($manager) {
    $DRAFT_SERVICE = new draft_service();

    try {
      $draft = $DRAFT_SERVICE->loadDraft($manager->draft_id);
    } catch (Exception $e) {
      throw new Exception("Unable to load draft.");
    }

    if ($draft->draft_id == 0 || !$draft->isUndrafted()) {
      throw new Exception("Draft is invalid.");
    }

    $old_order = $manager->draft_order;

    global $DBH; /* @var $DBH PDO */

    $sql = "DELETE FROM managers WHERE manager_id = " . (int) $manager->manager_id . " AND draft_id = " . (int) $manager->draft_id . " LIMIT 1";

    if ($DBH->exec($sql) === false) {
      throw new Exception("Unable to delete manager.");
    }

    try {
      $this->cascadeNewDraftOrder($manager, $old_order);
    } catch (Exception $e) {
      throw new Exception("Unable to delete manager: " . $e->getMessage());
    }

    return true;
  }

  /**
   * After a manager is deleted, all managers that are after him have their draft order bumped up by one all the way down, and this function does that
   * @param int $old_order The order of the manager being deleted
   * @return boolean on success, exception thrown otherwise
   */
  private function cascadeNewDraftOrder($manager, $old_order) {
    global $DBH; /* @var $DBH PDO */

    $manager_stmt = $DBH->prepare("SELECT manager_id FROM managers WHERE manager_id != ? AND draft_id = ? AND draft_order > ? ORDER BY draft_order ASC");
    $manager_stmt->bindParam(1, $manager->manager_id);
    $manager_stmt->bindParam(2, $manager->draft_id);
    $manager_stmt->bindParam(3, $old_order);

    $manager_stmt->execute();

    $inner_stmt = $DBH->prepare("UPDATE managers SET draft_order = ? WHERE manager_id = ?");
    $inner_stmt->bindParam(1, $old_order);
    $inner_stmt->bindParam(2, $inner_manager_id);

    while ($manager_row = $manager_stmt->fetch()) {
      $inner_manager_id = (int) $manager_row['manager_id'];
      if (!$inner_stmt->execute()) {
        throw new Exception("Unable to cascade manager draft order.");
      }

      $old_order++;
    }

    return true;
  }

  /**
   * Delete all managers associated with a single draft.
   * @param int $draft_id
   * @return boolean on success, exceptions thrown otherwise.
   */
  public function deleteManagersByDraft($draft_id) {
    $draft_id = (int) $draft_id;

    if ($draft_id == 0) {
      throw new Exception("Unable to delete managers - draft invalid.");
    }

    try {
      $managers = $this->getManagersByDraft($draft_id);
    } catch (Exception $e) {
      throw new Exception("Unable to delete managers: " . $e->getMessage());
    }

    $id_string = "0"; //TODO: Update this so it's cleaner? This is hacky.	

    foreach ($managers as $manager) {
      $id_string .= "," . (int) $manager->manager_id;
    }

    global $DBH; /* @var $DBH PDO */

    $sql = "DELETE FROM managers WHERE manager_id IN (" . $id_string . ")";

    if (!$DBH->exec($sql)) {
      throw new Exception("Unable to delete managers.");
    }

    return true;
  }

  /**
   * Given a single draft ID, get all managers for that draft
   * @param int $draft_id
   * @param bool $draft_order_sort Whether or not to sort by the manager's order in the draft. If false, manager_name is used
   * @return array of manager objects, exceptions thrown
   */
  public function getManagersByDraft($draft_id, $draft_order_sort = false, $order_sort = "ASC") {
    global $DBH; /* @var $DBH PDO */
    $managers = array();
    $draft_id = (int) $draft_id;

    if ($order_sort != "ASC" && $order_sort != "DESC")
      $order_sort = "ASC";

    $sort_by = $draft_order_sort ? "draft_order" : "manager_name";

    $stmt = $DBH->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY " . $sort_by . " " . $order_sort);

    if (!$stmt->bindParam(1, $draft_id)) {
      throw new Exception("Unable to get managers by draft.");
    }

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'manager_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to get managers by draft.");
    }

    while ($manager = $stmt->fetch())
      $managers[] = $manager;

    return $managers;
  }

  /**
   * Given a single draft ID, get the number of managers currently connected to that draft.
   * @param int $draft_id
   * @return int $number_of_managers
   */
  public function getCountOfManagersByDraft($draft_id) {
    global $DBH; /* @var $DBH PDO */
    $draft_id = (int) $draft_id;

    $stmt = $DBH->prepare("SELECT COUNT(manager_id) as count FROM managers WHERE draft_id = ? ORDER BY manager_name");
    $stmt->bindParam(1, $draft_id);

    if (!$stmt->execute()) {
      throw new Exception("Unable to get count of managers.");
    }

    $row = $stmt->fetch();

    return (int) $row['count'];
  }

}

?>
