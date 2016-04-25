<?php
namespace PhpDraft\Domain\Models;

class DepthChartDisplayModel {
  public function __construct($depth_chart_position_id, $position, $slots, $picks) {
    $this->depth_chart_position_id = $depth_chart_position_id;
    $this->position = $position;
    $this->slots = $slots;
    $this->picks = $picks;
  }

  public $depth_chart_position_id;
  public $position;
  public $slots;
  public $picks;
}