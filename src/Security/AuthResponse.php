<?php

namespace HootDraft\Security;

use HootDraft\Domain\Models\HootDraftResponse;

/** The "you are logged in as" payload shared by login, following the Email Verification Key link, Reset and /me. */
final class AuthResponse
{
  public static function for(User $user, ?HootDraftResponse $response = null): HootDraftResponse
  {
    $response ??= new HootDraftResponse(true);
    $response->name = $user->getName();
    $response->is_admin = $user->isAdmin();

    return $response;
  }
}
