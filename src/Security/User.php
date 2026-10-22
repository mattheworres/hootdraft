<?php

namespace HootDraft\Security;

use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The authenticated principal, built from a `users` row. Lives in the session, so it is refreshed from the
 * database on every request (see UserProvider::refreshUser): disabling a user or changing roles takes effect
 * immediately. getSalt() is only meaningful for accounts still on the legacy digest hash.
 */
final class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface
{
  /** @param string[] $roles */
  public function __construct(
    private int $id,
    private string $email,
    private string $name,
    private string $password,
    private ?string $salt,
    private array $roles,
    private bool $enabled,
  ) {
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getPassword(): string
  {
    return $this->password;
  }

  public function getSalt(): ?string
  {
    return $this->salt === '' ? null : $this->salt;
  }

  public function getRoles(): array
  {
    return $this->roles;
  }

  public function isEnabled(): bool
  {
    return $this->enabled;
  }

  public function isAdmin(): bool
  {
    return in_array('ROLE_ADMIN', $this->roles, true);
  }

  public function getUserIdentifier(): string
  {
    return $this->email;
  }

  /**
   * Called by UserProvider::upgradePassword after a rehash on login. The user object that is about to be
   * stored in the session must match the database, or the next request would see a "changed" user and log out.
   */
  public function upgradePasswordHash(string $newHash): void
  {
    $this->password = $newHash;
    $this->salt = '';
  }

  /** Keep the password hash out of the session file; Symfony compares the crc32c instead. */
  public function __serialize(): array
  {
    $data = (array) $this;
    $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

    return $data;
  }
}
