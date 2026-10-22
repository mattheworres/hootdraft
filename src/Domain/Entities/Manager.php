<?php
namespace HootDraft\Domain\Entities;

class Manager {
  /** @var int $manager_id The unique identifier for this manager */
  public $manager_id;

  /** @var int $draft_id Foreign key to the draft this manager belongs to */
  public $draft_id;

  /** @var string $manager_name Textual display name for each manager */
  public $manager_name;

  /** @var int $draft_order The order in which the manager makes a pick in the draft. */
  public $draft_order;

  //For uniqueness checks, use manager name:
  public function __toString() {
    return (string) $this->manager_name;
  }
}
