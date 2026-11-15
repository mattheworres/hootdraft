<?php

namespace HootDraft\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Accounts must be Enabled and Email Verified before they can log in. Both checks run after the password has been
 * checked, so a wrong password never reveals anything about the account (see LoginFailureHandler).
 */
class UserChecker implements UserCheckerInterface
{
  public function checkPreAuth(UserInterface $user): void
  {
  }

  public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
  {
    if (!$user instanceof User) {
      return;
    }

    // A Disabled user gets the generic failure, even when they are Email Unverified too
    if (!$user->isEnabled()) {
      throw new CustomUserMessageAccountStatusException('Account is not enabled.');
    }

    if (!$user->isEmailVerified()) {
      throw new EmailUnverifiedException($user->getEmail());
    }
  }
}
