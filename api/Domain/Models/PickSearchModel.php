<?php

namespace PhpDraft\Domain\Models;

class PickSearchModel {
  /** @var string */
  public $draft_id;

  /** @var string */
  public $keywords;

  /** @var string Three-char abbreviation */
  public $team;

  /** @var string Three-char abbreviation */
  public $position;

  /** @var string */
  public $player_results;

  public function __construct($draft_id, $keywords, $team, $position) {
    $this->draft_id = $draft_id;
    $this->keywords = $keywords;
    $this->team = $team;
    $this->position = $position;
  }

  /**
   * Used if the strict search returns no results, empty the results (for any reason) and set the count to zero.
   */
  public function emptyResultsData() {
    unset($this->player_results);
    $this->search_count = 0;
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
    return count($player_results);
  }
}