<?php
namespace PhpDraft\Domain\Entities;

/**
 * Represents a position that each team must fill in a given draft
 * Picks can be assigned to a depth chart position (different from a pick's "position",
 * which is defined at the application level and therefore more rigid)
 * Picks are auto-assigned when possible to depth chart positions, but will generally
 * require anonymous user updates to them
 */
class DepthChartPosition {
  /** @var int $id The unique identifier for this depth chart position */
  public $id;

  /** @var int $draft_id The foreign key link to the draft that this position belongs to */
  public $draft_id;

  /** @var string $position The position this object represents */
  public $position;

  /** @var int $slots The number of players this position can hold per team */
  public $slots;

  /** @var int $display_order The order in which to display this position for each team */
  public $display_order;

  //For purposes of validation, we implement toString to define equality by the lowercase position
  public function __toString() {
    return strtolower($this->position);
  }
}