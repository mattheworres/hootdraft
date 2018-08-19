<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
  public function Index(Application $app) {
    $isAuthenticated = false;
    $roles = array();

    //Leaving this here as we also grab roles below, but LoginUserService->GetCurrentUser() will fetch user via this method too:
    $token = $app['security']->getToken();

    if ($token !== null) {
      $usr = $token->getUser();

      $isAuthenticated = true;
      $roles = $usr->getRoles();
    }

    $response = array(
      "authenticated" => $isAuthenticated,
      "roles" => $roles
    );

    return $app->json($response, Response::HTTP_OK);
  }
}