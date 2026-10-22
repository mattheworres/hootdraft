<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\DepthChartPosition;

// Only LoadAll is ported so far (the draft read needs it); the rest arrives with 4.2.
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
}
