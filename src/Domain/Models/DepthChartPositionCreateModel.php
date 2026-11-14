<?php
namespace HootDraft\Domain\Models;

use HootDraft\Domain\Entities\DepthChartPosition;

class DepthChartPositionCreateModel {
  public function __construct() {
    $this->positions = array();
    $this->depthChartEnabled = false;
  }

  public $positions;
  public $depthChartEnabled;

  /**
   * True if these positions have the same names and slots as $stored, in the same order.
   * The order counts: display_order is stored.
   *
   * @param DepthChartPosition[] $stored
   */
  public function Matches(array $stored): bool {
    $summarize = fn (DepthChartPosition $position) => [(string) $position->position, (int) $position->slots];

    return array_map($summarize, $this->positions) === array_map($summarize, $stored);
  }
}
