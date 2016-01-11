<?php

namespace PhpDraft\Domain\Models;

class UserProfile {
  public function __construct($id, $email, $name) {
    $this->id = $id;
    $this->email = $email;
    $this->name = $name;
  }

  public $id;
  public $email;
  public $name;
}