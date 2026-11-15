<?php

namespace HootDraft\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
  public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
  {
    $user = $token->getUser();

    if (!$user instanceof User) {
      return new JsonResponse(['success' => false, 'errors' => ['Invalid credentials.']], Response::HTTP_BAD_REQUEST);
    }

    // A pending reset key is deliberately left alone: logging in with the old password must not
    // cancel a reset the owner asked for. Keys expire on their own (see LoginUserRepository::ResetKeyMatches).
    return new JsonResponse(AuthResponse::for($user));
  }
}
