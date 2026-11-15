<?php

namespace HootDraft\Domain\Models;

/** What LoginUserService::ResendEmailVerificationIfExpired did for an Email Unverified user who logged in. */
enum EmailVerificationResend
{
  /** The Email Verification Key is fresh (or the user is not Enabled and Email Unverified): nothing was sent. */
  case NotNeeded;
  /** The old key had expired or was missing: a new key was issued and its link emailed. */
  case Sent;
  /** A new link was due but the email could not go out. Nothing was saved, so the next login tries again. */
  case Failed;
}
