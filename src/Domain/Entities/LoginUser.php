<?php

namespace HootDraft\Domain\Entities;

final class LoginUser {
  /** Every user has this role. security.yaml gives it to an Admin through the role hierarchy. */
  public const ROLE_COMMISH = 'ROLE_COMMISH';
  public const ROLE_ADMIN = 'ROLE_ADMIN';

  public $id;
  /** Set only by an Admin. A Disabled user cannot log in. */
  public $enabled;
  /** Set by the user when they follow the emailed link for their current address. */
  public $emailVerified;
  public $email;
  public $password;
  public $salt;
  public $name;
  public $roles;
  public $resetKey;
  /** UTC "Y-m-d H:i:s" of when resetKey was issued; null whenever there is no key. */
  public $resetKeyCreated;
  public $emailVerificationKey;
  /** UTC "Y-m-d H:i:s" of when emailVerificationKey was issued; null whenever there is no key. */
  public $emailVerificationKeyCreated;
  public $creationTime;

  /** The role list from either storage shape: a list, or the comma-separated string in users.roles. */
  public static function parseRoles($roles): array {
    return is_array($roles) ? $roles : array_values(array_filter(array_map('trim', explode(',', (string) $roles))));
  }

  /** True when the role list makes the user an Admin. */
  public static function rolesIncludeAdmin(array $roles): bool {
    return in_array(self::ROLE_ADMIN, $roles, true);
  }

  public function isAdmin(): bool {
    return self::rolesIncludeAdmin(self::parseRoles($this->roles));
  }
}
