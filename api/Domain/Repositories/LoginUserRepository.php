<?php

namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\LoginUser;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class LoginUserRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function Load($username) {
    $user = new LoginUser();

    $load_stmt = $this->app['db']->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $user);
    $load_stmt->bindParam(1, strtolower($username));

    if (!$load_stmt->execute())
      throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));

    if (!$load_stmt->fetch())
      throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));

    return $user;
  }

  public function Create(LoginUser $user) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO users 
        (id, username, email, password, salt, name, roles, verificationKey) 
        VALUES 
        (NULL, ?, ?, ?, ?, ?, ?, ?)");

    $insert_stmt->bindParam(1, strtolower($user->username));
    $insert_stmt->bindParam(2, strtolower($user->email));
    $insert_stmt->bindParam(3, $user->password);
    $insert_stmt->bindParam(4, $user->salt);
    $insert_stmt->bindParam(5, $user->name);
    $insert_stmt->bindParam(6, explode(',', $user->roles));
    $insert_stmt->bindParam(7, $user->verificationKey);

    if (!$insert_stmt->execute()) {
      throw new Exception("Unable to create user.");
    }

    $user->id = (int) $this->app['db']->lastInsertId();

    return $user;
  }

  public function Update(LoginUser $user) {
    //TODO: Immplement.
  }

  public function Delete(LoginUser $user) {
    //TODO: Find use case & implement
  }
}