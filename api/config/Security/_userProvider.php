<?php
namespace PhpDraft\Config\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
 
class UserProvider implements UserProviderInterface
{
  public function loadUserByUsername($username)
  {
    // $stmt = $this->conn->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));
    // if (!$user = $stmt->fetch()) {
    
    $userQuery = \UsersQuery::create()->filterByUsername($username)->findOne();

    if(!$userQuery) {
      throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
    }

    return new PhpDraftSecurityUser($userQuery->getUsername(), $userQuery->getPassword(), '', explode(',', $userQuery->getRoles()));
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