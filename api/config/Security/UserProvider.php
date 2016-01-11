<?php
namespace PhpDraft\Config\Security;

use Silex\Application;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use PhpDraft\Domain\Entities\LoginUser;
 
class UserProvider implements UserProviderInterface
{
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function loadUserByUsername($email)
  {
    //Won't use repository here because we need to throw the UsernameNotFoundException to kick off Symfony denying the request
    $user = new LoginUser();
    $user_stmt = $this->app['db']->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $user_stmt->setFetchMode(\PDO::FETCH_INTO, $user);
    $user_stmt->bindParam(1, $email);

    if (!$user_stmt->execute())
      throw new UsernameNotFoundException(sprintf('Email "%s" does not exist.', $email));

    if (!$user_stmt->fetch())
      throw new UsernameNotFoundException(sprintf('Email "%s" does not exist.', $email));

    return new PhpDraftSecurityUser($user->email,
      $user->name,
      $user->password, 
      $user->salt, 
      explode(',', $user->roles), 
      $user->enabled, 
      $user->verificationKey);
  }

  public function refreshUser(UserInterface $user)
  {
    if (!$user instanceof User) {
        throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }

    return $this->loadUserByUsername($user->getUsername());
  }

  public function supportsClass($class)
  {
    return $this->userRepository->getClassName() === $class
      || is_subclass_of($class, $this->userRepository->getClassName());
    //return $class === 'Symfony\Component\Security\Core\User\User';
  }
}