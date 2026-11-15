<?php

namespace HootDraft\Domain\Models;

class PickSearchModel {
  /** @var string */
  public $draft_id;

  /** @var string */
  public $keywords;

  /** @var string Three-char abbreviation */
  public $team;

  /** @var string Three-char abbreviation */
  public $position;

  /** @var string Sorting by player pick, ASC or DESC */
  public $sort;

  /** @var array */
  public $player_results;

  public function __construct($draft_id, $keywords, $team, $position, $sort) {
    $this->draft_id = $draft_id;
    $this->keywords = $keywords;
    $this->team = $team;
    $this->position = $position;
    // The sort is interpolated into the ORDER BY clause, so only the two real directions get through
    $this->sort = strtoupper((string) $sort) === 'ASC' ? 'ASC' : 'DESC';
  }

  public function hasName() {
    return isset($this->keywords) && strlen($this->keywords) > 0;
  }

  public function hasTeam() {
    return isset($this->team) && strlen($this->team) > 0;
  }

  public function hasPosition() {
    return isset($this->position) && strlen($this->position) > 0;
  }

  public function searchCount() {
    return count($this->player_results ?? array());
  }
}
