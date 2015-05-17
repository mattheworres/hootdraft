<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
  public function Index(Application $app) {
    $isAuthenticated = false;
    $roles = array();

    $token = $app['security']->getToken();

    if($token !== null) {
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