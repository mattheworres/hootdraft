<?php

namespace HootDraft\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/** Accounts must be verified (enabled) before they can log in. */
class UserChecker implements UserCheckerInterface
{
  public function checkPreAuth(UserInterface $user): void
  {
    if ($user instanceof User && !$user->isEnabled()) {
      throw new CustomUserMessageAccountStatusException('Account is not enabled.');
    }
  }

  public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
  {
  }
}
