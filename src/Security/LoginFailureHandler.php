<?php

namespace HootDraft\Security;

use HootDraft\Domain\Models\HootDraftResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/** Always the same message, whether the email is unknown, the password is wrong or the account is disabled. */
class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
  {
    $response = new HootDraftResponse(false, ['Invalid credentials.']);

    return new JsonResponse($response, $response->responseType());
  }
}
