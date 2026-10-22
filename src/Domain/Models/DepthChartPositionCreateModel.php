<?php
namespace HootDraft\Domain\Models;

class DepthChartPositionCreateModel {
  public function __construct() {
    $this->positions = array();
    $this->depthChartEnabled = false;
  }

  public $positions;
  public $depthChartEnabled;
}
