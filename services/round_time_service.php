<?php

  /**
   * Class round_time_service
   */
  class round_time_service {
    /**
     * @param int $id
     * @throws Exception
     */
    public function loadRoundTime($id = 0) {
      global $DBH; /* @var $DBH PDO */
      $id = (int) $id;

      if ($id == 0) {
        throw new Exception("Unable to load round time - invalid ID.");
      }

      $round_time_stmt = $DBH->prepare("SELECT * FROM round_times WHERE round_time_id = ? LIMIT 1");
      $round_time_stmt->setFetchMode(PDO::FETCH_INTO, $this);
      $round_time_stmt->bindParam(1, $id);

      if (!$round_time_stmt->execute()) {
        throw new Exception("Unable to load round time - PDO error:" . $DBH->errorInfo());
      }

      if (!$round_time_stmt->fetch()) {
        throw new Exception("Unable to load round time - PDO error:" . $DBH->errorInfo());
      }

      return;
    }

    public function getRoundTimes($draft_id = 0) {
      global $DBH; /* @var $DBH PDO */
      $draft_id = (int) $draft_id;

      if($draft_id == 0) {
        throw new Exception("Unable to get round times: invalid draft id");
      }

      $round_times_stmt = $DBH->prepare("SELECT * FROM round_times WHERE draft_id = ?");
      $round_times_stmt->setFetchMode(PDO::FETCH_CLASS, 'round_time_object');
      $round_times_stmt->bindParam(1, $draft_id);

      $round_times = array();

      if(!$round_times_stmt->execute()) {
        throw new Exception("Unable to load round times - PDO error: " . $DBH->errorInfo());
      }

      while($round_time = $round_times_stmt->fetch()) {
        $round_times[] = $round_time;
      }

      return $round_times;
    }

    /** getRoundTimeByDraftRound Provided a draft id and round number, get the round time (if it exists)
     * (Draft ID provided in service constructor)
     *
     * @param int $pick_round
     * @param int $draft_id
     * @throws Exception
     * @return null|round_time_object
     */
    public function getRoundTimeByDraftRound($pick_round = 0, $draft_id = 0) {
      global $DBH; /* @var $DBH PDO */
      $draft_id = (int) $draft_id;
      $pick_round = (int) $pick_round;

      if ($pick_round == 0 || $draft_id == 0) {
        throw new Exception("Unable to get round time - invalid pick round or draft id");
      }

      $round_time = new round_time_object();

      $static_round_time_stmt = $DBH->prepare("SELECT * FROM round_times WHERE draft_id = ? AND is_static_time = 1 LIMIT 1");
      $static_round_time_stmt->setFetchMode(PDO::FETCH_INTO, $round_time);
      $static_round_time_stmt->bindParam(1, $_draft_id);

      if(!$static_round_time_stmt->execute()) {
        throw new Exception("Unable to get static round time.");
      }

      if($static_round_time_stmt->rowCount() > 0) {
        return $round_time;
      }

      $round_time_stmt = $DBH->prepare("SELECT * FROM round_times WHERE draft_id = ? AND draft_round = ? LIMIT 1");
      $round_time_stmt->setFetchMode(PDO::FETCH_INTO, $round_time);
      $round_time_stmt->bindParam(1, $_draft_id);
      $round_time_stmt->bindParam(2, $pick_round);

      if (!$round_time_stmt->execute()) {
        throw new Exception("Unable to load round time - PDO error:" . $DBH->errorInfo());
      }

      if($round_time_stmt->rowCount() == 0) {
        return null;
      }

      if (!$round_time_stmt->fetch()) {
        throw new Exception("Unable to load round time - PDO error:" . $DBH->errorInfo());
      }

      return $round_time;
    }

    /** Remove all round times for a given draft.
     * @param int $draft_id
     * @throws Exception
     */
    public function removeRoundTimesByDraft($draft_id = 0) {
      global $DBH; /* @var $DBH PDO */
      $draft_id = (int) $draft_id;

      if($draft_id == 0) {
        throw new Exception("Unable to remove round times - invalid draft id");
      }

      $delete_round_times_stmt = $DBH->prepare("DELETE FROM round_times WHERE draft_id = ?");
      $delete_round_times_stmt->bindParam(1, $draft_id);

      if(!$delete_round_times_stmt->execute()) {
        throw new Exception("Unable to delete round times: " . $DBH->errorInfo());
      }
    }

    /**
     * Check the validity of round timer object and return array of error descriptions if invalid.
     *
     * @param $round_timer
     * @return array/string errors
     */
    public function getValidity($round_timer) {
      $errors = array();

      if (!isset($round_timer->draft_id) || $round_timer->draft_id == 0)
        $errors[] = "Draft ID is empty.";

      if(!isset($round_timer->is_static_time))
        $errors[] = "is_static_time is not set.";

      if(isset($round_timer->is_static_time) && !$round_timer->is_static_time && !isset($round_timer->draft_round)) {
        $errors[] = "Round not set on a non-static time.";
      }

      if(!isset($round_timer->round_time_seconds) || $round_timer->round_time_seconds <= 0)
        $errors[] = "Invalid round time.";

      return $errors;
    }

    /**
     * Saves the round time object
     *
     * @param $round_time
     * @throws Exception
     * @return round_time_object $manager on success, exception thrown otherwise
     */
    public function saveRoundTime($round_time) {
      global $DBH; /* @var $DBH PDO */
      $isStaticTime = $round_time->is_static_time ? 1 : 0;
      $save_stmt = $DBH->prepare("INSERT INTO round_times (draft_id, is_static_time, draft_round, round_time_seconds) VALUES (?, ?, ?, ?)");
      $save_stmt->bindParam(1, $round_time->draft_id);
      $save_stmt->bindParam(2, $isStaticTime);
      $save_stmt->bindParam(3, $round_time->draft_round);
      $save_stmt->bindParam(4, $round_time->round_time_seconds);

      if (!$save_stmt->execute()) {
        throw new Exception("Unable to save round time.");
      }

      $round_time->round_time_id = (int) $DBH->lastInsertId();

      return $round_time;
    }
  }
?>
