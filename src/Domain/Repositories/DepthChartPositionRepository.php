<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;

class DepthChartPositionRepository {
  public function __construct(private \PDO $pdo) {
  }

  public function LoadAll($draft_id) {
    $positions = array();

    $stmt = $this->pdo->prepare("SELECT d.* " .
            "FROM depth_chart_positions d " .
            "WHERE d.draft_id = ? " .
            "ORDER BY d.display_order");

    $stmt->bindParam(1, $draft_id, \PDO::PARAM_INT);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, DepthChartPosition::class);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load updated positions.");
    }

    while ($position = $stmt->fetch()) {
      $positions[] = $position;
    }

    return $positions;
  }

  public function Save(DepthChartPositionCreateModel $depthChartPositionCreateModel, $draft_id) {
    $insertPositionStmt = $this->pdo->prepare("INSERT INTO depth_chart_positions
      (draft_id, position, slots, display_order)
      VALUES
      (:draft_id, :position, :slots, :display_order)");

    $newPositions = array();

    foreach ($depthChartPositionCreateModel->positions as $position) {
      $insertPositionStmt->bindValue(":draft_id", $draft_id);
      $insertPositionStmt->bindValue(":position", $position->position);
      $insertPositionStmt->bindValue(":slots", $position->slots);
      $insertPositionStmt->bindValue(":display_order", $position->display_order);

      if (!$insertPositionStmt->execute()) {
        throw new \Exception("Unable to create depth chart positions for $draft_id");
      }

      // The old code set `round_time_id` here (a copy-paste from the round time repository); `id` is the property that exists
      $position->id = (int)$this->pdo->lastInsertId();
      $newPositions[] = $position;
    }

    return $newPositions;
  }

  public function DeleteAllDepthChartPositions($draft_id) {
    $delete_stmt = $this->pdo->prepare("DELETE FROM depth_chart_positions WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if (!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete existing depth chart positions.");
    }
  }
}
