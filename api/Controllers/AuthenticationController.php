<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use PhpDraft\Domain\Entities\LoginUser;

class AuthenticationController
{
  //See Commish->Index for permissions check

  public function Login(Application $app, Request $request) {
    $vars = json_decode($request->getContent(), true);
    $email = $request->get('_email');
    $password = $request->get('_password');

    try {
      if($app['phpdraft.LoginUserValidator']->IsLoginUserValid($email, $password)) {
        throw new UsernameNotFoundException(sprintf('Email %s does not exist', $email));
      }

      $user = $app['users']->loadUserByUsername($email);

      if (!$user->isEnabled() || !$app['security.encoder.digest']->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
        throw new UsernameNotFoundException(sprintf('Email "%s" does not exist', $email));
      } else {
        $response = [
          'success' => true,
          'token' => $app['security.jwt.encoder']->encode(['name' => $user->getUsername()]),
        ];

        //If user is enabled, provided valid password and has a verification (pwd reset) key, wipe it (no longer needed)
        if($user->hasVerificationKey()) {
          $app['phpdraft.LoginUserRepository']->EraseVerificationKey($user->getEmail());
        }
      }
    } catch (UsernameNotFoundException $e) {
      $response = [
        'success' => false,
        'errors' => 'Invalid credentials.',
      ];
    }

    return $app->json($response, Response::HTTP_OK);
  }

  public function Register(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsRegistrationUserValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $user = new LoginUser();

    $user->email = $request->get('_email');
    $user->password = $request->get('_password');
    $user->name = $request->get('_name');
    
    $response = $app['phpdraft.LoginUserService']->CreateUnverifiedNewUser($user);

    return $app->json($response, $response->responseType());
  }

  public function VerifyAccount(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsVerificationValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $email = urldecode($request->get('_email'));

    $user = $app['phpdraft.LoginUserRepository']->Load($email);

    $response = $app['phpdraft.LoginUserService']->VerifyUser($user);

    return $app->json($response, $response->responseType());
  }

  public function LostPassword(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsForgottenPasswordUserValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $email = $request->get('_email');

    $user = $app['phpdraft.LoginUserRepository']->Load($email);

    $response = $app['phpdraft.LoginUserService']->BeginForgottenPasswordProcess($user);

    return $app->json($response, $response->responseType());
  }

  public function ResetPassword(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsResetPasswordRequestValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $email = urldecode($request->get('_email'));
    $password = $request->get('_password');

    $user = $app['phpdraft.LoginUserRepository']->Load($email);

    $user->password = $password;

    $response = $app['phpdraft.LoginUserService']->ResetPassword($user);

    return $app->json($response, $response->responseType());
  }
}