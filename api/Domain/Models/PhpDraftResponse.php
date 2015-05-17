<?php

namespace PhpDraft\Domain\Models;

class PhpDraftResponse {
  public function __construct($success = false, $errors = array()) {
    $this->success = $success;
    $this->errors = $errors;
  }

  public $success;
  public $errors;
}