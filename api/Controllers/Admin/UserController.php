<?php

namespace PhpDraft\Controllers\Admin;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Draft;

class UserController
{
  public function Get(Application $app, Request $request) {
    $response = $app['phpdraft.LoginUserService']->GetAll();

    return $app->json($response, $response->responseType());
  }

  public function Update(Application $app, Request $request) {
    $user_id = $request->get('user_id');
    $user = $app['phpdraft.LoginUserRepository']->LoadById($user_id);
    $user->email = $request->get('email');
    $user->name = $request->get('name');
    $user->enabled = $request->get('enabled');

    $rolesJson = $request->get('roles');

    $user->roles = implode(',', $rolesJson);

    $validity = $app['phpdraft.LoginUserValidator']->IsAdminUserUpdateValid($user);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $app['phpdraft.LoginUserRepository']->Update($user);

    $response = new PhpDraftResponse(true, array());

    return $app->json($response, $response->responseType());
  }

  
}