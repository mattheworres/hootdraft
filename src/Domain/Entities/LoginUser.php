<?php

namespace HootDraft\Domain\Entities;

class LoginUser {
  public $id;
  public $enabled;
  public $email;
  public $password;
  public $salt;
  public $name;
  public $roles;
  public $verificationKey;
  /** UTC "Y-m-d H:i:s" of when verificationKey was issued; null whenever there is no key. */
  public $verificationKeyCreated;
  public $creationTime;

  /** The role list from either storage shape: a list, or the comma-separated string in users.roles. */
  public static function parseRoles($roles): array {
    return is_array($roles) ? $roles : array_map('trim', explode(',', (string) $roles));
  }
}
