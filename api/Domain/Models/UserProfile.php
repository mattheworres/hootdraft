<?php

namespace PhpDraft\Domain\Models;

class UserProfile {
  public function __construct($id, $username, $email) {
    $this->id = $id;
    $this->username = $username;
    $this->email = $email;
  }

  public $id;
  public $username;
  public $email;
}