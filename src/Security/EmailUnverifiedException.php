<?php

namespace HootDraft\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * Thrown after a correct password for an Enabled user whose email address is not Email Verified. It extends the
 * "custom message" exception because Symfony turns every other account status exception into a plain bad credentials
 * failure, which would hide this case from LoginFailureHandler.
 */
class EmailUnverifiedException extends CustomUserMessageAccountStatusException
{
  public function __construct(private string $email)
  {
    parent::__construct('Email Unverified.');
  }

  public function getEmail(): string
  {
    return $this->email;
  }
}
