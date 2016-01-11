<?php
namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use \PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\Pick;

class CommishController {
  public function SearchPublicCommissioners(Application $app, Request $request) {
    $searchTerm = substr($request->get('searchTerm'), 0, 255);

    $response = $app['phpdraft.LoginUserService']->SearchCommissioners($searchTerm);

    return $app->json($response, $response->responseType());
  }

  public function GetPublicCommissioner(Application $app, Request $request) {
    $commish_id = $request->get('commish_id');

    $response = $app['phpdraft.LoginUserService']->GetCommissioner($commish_id);

    return $app->json($response, $response->responseType());
  }
}