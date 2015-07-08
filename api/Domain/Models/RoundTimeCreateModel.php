<?php

namespace PhpDraft\Domain\Models;

class RoundTimeCreateModel {
  public function __construct() {
    $this->roundTimes = array();
    $this->isRoundTimesEnabled = false;
  }

  public $roundTimes;
  public $isRoundTimesEnabled;
}