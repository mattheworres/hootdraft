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
    $user_id = $request->get('id');
    $user = $app['phpdraft.LoginUserRepository']->LoadById($user_id);
    $user->email = $request->get('email');
    $user->name = $request->get('name');
    $user->roles = $request->get('roles');

    //Coerce down to a 1 or 0 type
    $enabVal = $request->get('enabled');
    $enabled = $request->get('enabled') == true;
    $user->enabled = $enabled ? 1 : 0;

    $validity = $app['phpdraft.LoginUserValidator']->IsAdminUserUpdateValid($user);

    if (!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $app['phpdraft.LoginUserRepository']->Update($user);

    $response = new PhpDraftResponse(true, array());

    return $app->json($response, $response->responseType());
  }

  public function Delete(Application $app, Request $request) {
    $user_id = $request->get('user_id');
    $user = $app['phpdraft.LoginUserRepository']->LoadById($user_id);

    if ($app['phpdraft.LoginUserService']->CurrentUserIsAdmin($user)) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete user - user is admin.";

      return $app->json($response, $response->responseType());
    }

    $response = $app['phpdraft.LoginUserService']->DeleteUser($user);

    return $app->json($response, $response->responseType());
  }
}