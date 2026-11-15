<?php

namespace HootDraft\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/** Anonymous request to a protected URL: 401, which the AngularJS client turns into a redirect to /login. */
class UnauthorizedEntryPoint implements AuthenticationEntryPointInterface
{
  public function start(Request $request, ?AuthenticationException $authException = null): Response
  {
    return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
  }
}
