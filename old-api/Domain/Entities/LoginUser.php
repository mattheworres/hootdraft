<?php

namespace PhpDraft\Domain\Entities;

class LoginUser {
  public $id;
  public $enabled;
  public $email;
  public $password;
  public $salt;
  public $name;
  public $roles;
  public $verificationKey;
}