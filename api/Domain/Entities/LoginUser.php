<?php

namespace PhpDraft\Domain\Entities;

class LoginUser {
  public $id;
  public $enabled;
  public $username;
  public $email;
  public $password;
  public $salt;
  public $name;
  public $roles;
  public $verificationKey;
}