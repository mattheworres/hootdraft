<?php
namespace PhpDraft\Config\Security;

use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface {
  /**
   * Starts the authentication scheme.
   *
   * @param Request $request The request that resulted in an AuthenticationException
   * @param AuthenticationException $authException The exception that started the authentication process
   *
   * @return Response
   */
  public function start(Request $request, AuthenticationException $authException = null)
  {
      $array = array('success' => false);
      
      if ($authException) {
        $array['error'] = $authException;
      }

      $response = new Response(json_encode($array), 401);
      $response->headers->set('Content-Type', 'application/json');

      return $response;
  }
}