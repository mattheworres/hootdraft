<?php

namespace HootDraft\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** Loads users straight from the `users` table (replaces the Silex UserProvider). */
class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
  public function __construct(private \PDO $pdo)
  {
  }

  public function loadUserByIdentifier(string $identifier): UserInterface
  {
    $stmt = $this->pdo->prepare('SELECT id, email, name, password, salt, roles, enabled FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower($identifier)]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$row) {
      throw new UserNotFoundException(sprintf('Email "%s" does not exist.', $identifier));
    }

    return new User(
      (int) $row['id'],
      $row['email'],
      $row['name'],
      $row['password'],
      $row['salt'],
      array_values(array_filter(array_map('trim', explode(',', (string) $row['roles'])))),
      (bool) $row['enabled'],
    );
  }

  public function refreshUser(UserInterface $user): UserInterface
  {
    if (!$user instanceof User) {
      throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
    }

    $refreshed = $this->loadUserByIdentifier($user->getUserIdentifier());

    // Unverified / disabled since logging in: drop the session
    if (!$refreshed->isEnabled()) {
      throw new UserNotFoundException('User is disabled.');
    }

    return $refreshed;
  }

  public function supportsClass(string $class): bool
  {
    return $class === User::class || is_subclass_of($class, User::class);
  }

  /** Called after a successful login when the stored hash is legacy or outdated, to store a modern one. */
  public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
  {
    if (!$user instanceof User) {
      return;
    }

    // The per-user salt only existed for the legacy digest, a modern hash carries its own
    $stmt = $this->pdo->prepare("UPDATE users SET password = ?, salt = '' WHERE id = ?");
    $stmt->execute([$newHashedPassword, $user->getId()]);

    $user->upgradePasswordHash($newHashedPassword);
  }
}
