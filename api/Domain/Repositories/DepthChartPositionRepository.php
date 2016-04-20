<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\DepthChartPositionCreateModel;

class DepthChartPositionRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function LoadAll($draft_id) {
    $positions = array();
    
    $stmt = $this->app['db']->prepare("SELECT d* ".
            "WHERE d.draft_id = ? " .
            "ORDER BY d.display_order");
    
    $stmt->bindParam(1, $draft_id);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\DepthChartPosition');
    
    if(!$stmt->execute()) {
      throw new \Exception("Unable to load updated positions.");
    }
    
    while($position = $stmt->fetch()) {
      $positions[] = $position;
    }
    
    return $positions;
  }

  public function Save(DepthChartPositionCreateModel $depthChartPositionCreateModel, $draft_id) {
    $insertPositionStmt = $this->app['db']->prepare("INSERT INTO depth_chart_positions 
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
        throw new \Exception("Unable to create depth chart positions for $position->draft_id");
      }

      $position->round_time_id = (int)$this->app['db']->lastInsertId();
      $newPositions[] = $position;
    }

    return $newPositions;
  }

  public function DeleteAllDepthChartPositions($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM depth_chart_positions WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete existing depth chart positions.");
    }
  }
}