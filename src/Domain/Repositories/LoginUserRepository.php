<?php

namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\LoginUser;

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

  public function LoadById($id) {
    $user = new LoginUser();

    $id = (int)$id;

    $load_stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $user);
    $load_stmt->bindParam(1, $id);

    if (!$load_stmt->execute() || !$load_stmt->fetch()) {
      throw new \Exception(sprintf('User #%s does not exist.', $id));
    }

    return $user;
  }

  /** Every user without the password, salt, reset key and email verification key. Admin only. */
  public function LoadAll() {
    $load_stmt = $this->pdo->prepare("SELECT * FROM users");
    $load_stmt->setFetchMode(\PDO::FETCH_CLASS, LoginUser::class);

    $users = array();

    if (!$load_stmt->execute()) {
      throw new \Exception("Unable to load users.");
    }

    while ($user = $load_stmt->fetch()) {
      $users[] = $this->_ScrubUser($user);
    }

    return $users;
  }

  public function Delete(LoginUser $user) {
    $delete_stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
    $delete_stmt->bindParam(1, $user->id);

    if (!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete user #$user->id");
    }

    return;
  }

  public function GetRoles() {
    $roles = array();

    $roles['ROLE_COMMISH'] = "Commissioner";
    $roles['ROLE_ADMIN'] = "Administrator";

    return $roles;
  }

  /** Public view of a commissioner: id and name only. */
  public function LoadPublicById($id) {
    $user = new LoginUser();

    $id = (int)$id;

    $load_stmt = $this->pdo->prepare("SELECT id, name FROM users WHERE id = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $user);
    $load_stmt->bindParam(1, $id);

    if (!$load_stmt->execute() || !$load_stmt->fetch()) {
      throw new \Exception(sprintf('User #%s does not exist.', $id));
    }

    return $this->_ScrubPublicUser($user);
  }

  public function SearchCommissioners($searchTerm) {
    $searchTerm = trim((string) $searchTerm);

    if ($searchTerm === '') {
      return array();
    }

    $searchTerm = '%' . addcslashes($searchTerm, '\\%_') . '%';
    $search_stmt = $this->pdo->prepare("SELECT id, name FROM users WHERE name LIKE ?");

    $search_stmt->setFetchMode(\PDO::FETCH_CLASS, LoginUser::class);
    $search_stmt->bindParam(1, $searchTerm);

    $users = array();

    if (!$search_stmt->execute()) {
      throw new \Exception("Unable to load users");
    }

    while ($user = $search_stmt->fetch()) {
      $users[] = $this->_ScrubPublicUser($user);
    }

    return $users;
  }

  /** Leaves only what was selected (id, name) so nothing else serializes into the public JSON. */
  private function _ScrubPublicUser(LoginUser $user) {
    unset($user->enabled);
    unset($user->emailVerified);
    unset($user->email);
    unset($user->password);
    unset($user->salt);
    unset($user->roles);
    unset($user->resetKey);
    unset($user->resetKeyCreated);
    unset($user->emailVerificationKey);
    unset($user->emailVerificationKeyCreated);
    unset($user->creationTime);

    return $user;
  }

  public function Create(LoginUser $user) {
    $insert_stmt = $this->pdo->prepare("INSERT INTO users
        (id, email, password, salt, name, roles, resetKey, resetKeyCreated,
          emailVerificationKey, emailVerificationKeyCreated, enabled, emailVerified, creationTime)
        VALUES
        (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");

    $ok = $insert_stmt->execute([
      strtolower($user->email),
      $user->password,
      $user->salt,
      $user->name,
      implode(',', $user->roles),
      $user->resetKey,
      $user->resetKeyCreated,
      $user->emailVerificationKey,
      $user->emailVerificationKeyCreated,
      (int) $user->enabled,
      (int) $user->emailVerified,
    ]);

    if (!$ok) {
      throw new \Exception("Unable to create user.");
    }

    $user->id = (int) $this->pdo->lastInsertId();

    return $user;
  }

  /** Saves everything except `enabled`: only an Admin changes that (see UpdateAsAdmin). */
  public function Update(LoginUser $user) {
    $update_stmt = $this->pdo->prepare("UPDATE users
        SET email = ?, password = ?, salt = ?,
          name = ?, roles = ?, resetKey = ?, resetKeyCreated = ?,
          emailVerificationKey = ?, emailVerificationKeyCreated = ?, emailVerified = ?
        WHERE id = ?");

    $ok = $update_stmt->execute([
      $user->email,
      $user->password,
      $user->salt,
      $user->name,
      is_array($user->roles) ? implode(',', $user->roles) : $user->roles,
      $user->resetKey,
      $user->resetKeyCreated,
      $user->emailVerificationKey,
      $user->emailVerificationKeyCreated,
      (int) $user->emailVerified,
      $user->id,
    ]);

    if (!$ok) {
      throw new \Exception("Unable to update user.");
    }

    return $user;
  }

  /** The Admin's edit: email, name, roles and `enabled`. Email Verified and the keys are not the Admin's to change. */
  public function UpdateAsAdmin(LoginUser $user) {
    $update_stmt = $this->pdo->prepare("UPDATE users SET email = ?, name = ?, roles = ?, enabled = ? WHERE id = ?");

    $ok = $update_stmt->execute([
      $user->email,
      $user->name,
      is_array($user->roles) ? implode(',', $user->roles) : $user->roles,
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

  /** True for a user who is Enabled and Email Verified: the only kind who may Reset a password. */
  public function IsEnabledAndEmailVerified($email) {
    $stmt = $this->pdo->prepare("SELECT enabled, emailVerified FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([strtolower($email)]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    return $row !== false && (int) $row['enabled'] === 1 && (int) $row['emailVerified'] === 1;
  }

  public function EmailIsUnique($email, $id = null) {
    return !$this->EmailExists($email, $id);
  }

  /** True when the Reset Key is the one issued to this email and was issued no more than $maxAgeSeconds ago. */
  public function ResetKeyMatches($email, $resetKey, int $maxAgeSeconds) {
    return $this->keyMatches('resetKey', 'resetKeyCreated', $email, $resetKey, $maxAgeSeconds);
  }

  /** True when the Email Verification Key is the one issued to this email and was issued no more than $maxAgeSeconds ago. */
  public function EmailVerificationKeyMatches($email, $emailVerificationKey, int $maxAgeSeconds) {
    return $this->keyMatches('emailVerificationKey', 'emailVerificationKeyCreated', $email, $emailVerificationKey, $maxAgeSeconds);
  }

  /**
   * The key is compared here rather than in SQL so the comparison is constant time.
   * The column names are never user input: only the two callers above pass them.
   */
  private function keyMatches(string $keyColumn, string $createdColumn, $email, $key, int $maxAgeSeconds) {
    $key_stmt = $this->pdo->prepare("SELECT $keyColumn AS storedKey,
        ($createdColumn IS NOT NULL AND $createdColumn > UTC_TIMESTAMP() - INTERVAL ? SECOND) AS fresh
        FROM users WHERE email = ? LIMIT 1");
    $key_stmt->execute([$maxAgeSeconds, strtolower($email)]);
    $row = $key_stmt->fetch(\PDO::FETCH_ASSOC);

    return $row !== false
      && $row['storedKey'] !== null
      && (int) $row['fresh'] === 1
      && hash_equals((string) $row['storedKey'], (string) $key);
  }

  private function _ScrubUser(LoginUser $user) {
    unset($user->password);
    unset($user->salt);
    unset($user->resetKey);
    unset($user->resetKeyCreated);
    unset($user->emailVerificationKey);
    unset($user->emailVerificationKeyCreated);

    return $user;
  }
}
