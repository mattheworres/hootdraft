<?php

namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\LoginUser;

// Only the methods the authentication flows need are ported so far; the rest (LoadAll, Search, Delete,
// public lookups, ...) arrive with their controllers in step 4. See old-api for the originals.
class LoginUserRepository {
  public function __construct(private \PDO $pdo) {
  }

  public function Load($email) {
    $user = new LoginUser();
    $email = strtolower($email);

    $load_stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $user);

    if (!$load_stmt->execute([$email]) || !$load_stmt->fetch()) {
      throw new \Exception(sprintf('Email "%s" does not exist.', $email));
    }

    return $user;
  }

  public function Create(LoginUser $user) {
    $insert_stmt = $this->pdo->prepare("INSERT INTO users
        (id, email, password, salt, name, roles, verificationKey, verificationKeyCreated, creationTime)
        VALUES
        (NULL, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");

    $ok = $insert_stmt->execute([
      strtolower($user->email),
      $user->password,
      $user->salt,
      $user->name,
      implode(',', $user->roles),
      $user->verificationKey,
      $user->verificationKeyCreated,
    ]);

    if (!$ok) {
      throw new \Exception("Unable to create user.");
    }

    $user->id = (int) $this->pdo->lastInsertId();

    return $user;
  }

  public function Update(LoginUser $user) {
    $update_stmt = $this->pdo->prepare("UPDATE users
        SET email = ?, password = ?, salt = ?,
          name = ?, roles = ?, verificationKey = ?, verificationKeyCreated = ?, enabled = ?
        WHERE id = ?");

    $ok = $update_stmt->execute([
      $user->email,
      $user->password,
      $user->salt,
      $user->name,
      is_array($user->roles) ? implode(',', $user->roles) : $user->roles,
      $user->verificationKey,
      $user->verificationKeyCreated,
      (int) $user->enabled,
      $user->id,
    ]);

    if (!$ok) {
      throw new \Exception("Unable to update user.");
    }

    return $user;
  }

  public function NameIsUnique($name, $id = null) {
    $name = strtolower($name);

    if ($id == null) {
      $name_stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE name LIKE ? LIMIT 1");
      $name_stmt->execute([$name]);
    } else {
      $name_stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE name LIKE ? AND id <> ? LIMIT 1");
      $name_stmt->execute([$name, $id]);
    }

    return $name_stmt->fetch() === false;
  }

  public function EmailExists($email, $id = null) {
    $email = strtolower($email);

    if ($id == null) {
      $email_stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
      $email_stmt->execute([$email]);
    } else {
      $email_stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1");
      $email_stmt->execute([$email, $id]);
    }

    return $email_stmt->fetch() !== false;
  }

  public function IsEnabled($email) {
    $stmt = $this->pdo->prepare("SELECT enabled FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([strtolower($email)]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    return $row !== false && (int) $row['enabled'] === 1;
  }

  public function EmailIsUnique($email) {
    return !$this->EmailExists($email);
  }

  /**
   * True when the key is the one issued to this email and was issued no more than $maxAgeSeconds ago.
   * The key is compared here rather than in SQL so the comparison is constant time.
   */
  public function VerificationMatches($email, $verificationKey, int $maxAgeSeconds) {
    $verification_stmt = $this->pdo->prepare("SELECT verificationKey,
        (verificationKeyCreated IS NOT NULL AND verificationKeyCreated > UTC_TIMESTAMP() - INTERVAL ? SECOND) AS fresh
        FROM users WHERE email = ? LIMIT 1");
    $verification_stmt->execute([$maxAgeSeconds, strtolower($email)]);
    $row = $verification_stmt->fetch(\PDO::FETCH_ASSOC);

    return $row !== false
      && $row['verificationKey'] !== null
      && (int) $row['fresh'] === 1
      && hash_equals((string) $row['verificationKey'], (string) $verificationKey);
  }
}
