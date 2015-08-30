<?php

namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;

class AuthenticationController
{
  //See Commish->Index for permissions check

  public function Login(Application $app, Request $request) {
    $email = $request->get('_email');
    $password = $request->get('_password');

    $response = new PhpDraftResponse();

    try {
      if($app['phpdraft.LoginUserValidator']->IsLoginUserValid($email, $password)) {
        throw new UsernameNotFoundException(sprintf('Email %s does not exist', $email));
      }

      $user = $app['users']->loadUserByUsername($email);

      if (!$user->isEnabled() || !$app['security.encoder.digest']->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
        throw new UsernameNotFoundException(sprintf('Email %s does not exist', $email));
      } else {
        $now = new \DateTime("now", new \DateTimeZone('GMT'));
        $interval = new \DateInterval('P0Y0M0DT0H0M' . AUTH_SECONDS . 'S');
        $auth_timeout = $now->add($interval);

        $response->success = true;
        $response->name = $user->getName();
        $response->token = $app['security.jwt.encoder']->encode(['name' => $user->getUsername()]);
        $response->auth_timeout = $auth_timeout->format('Y-m-d H:i:s');

        //If user is enabled, provided valid password and has a verification (pwd reset) key, wipe it (no longer needed)
        if($user->hasVerificationKey()) {
          $app['phpdraft.LoginUserRepository']->EraseVerificationKey($user->getEmail());
        }
      }
    } catch (UsernameNotFoundException $e) {
      $response->success = false;
      $response->errors[] =  'Invalid credentials.';
    }

    return $app->json($response, $response->responseType());
  }

  public function Register(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsRegistrationUserValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    //TODO: Remove. Temporary workaround to disable Recaptcha verifications on localhost
    $whitelist = array(
      '127.0.0.1',
      '::1'
    );

    $captcha = $request->get('_recaptcha');
    $user_ip = $request->getClientIp();

    if(!in_array($user_ip, $whitelist)){

      $recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);
      $recaptcha_response = $recaptcha->verify($captcha, $user_ip);

      if(!$recaptcha_response->isSuccess()) {
        $response = new PhpDraftResponse(false, array());
        $response->errors = $recaptcha_response->getErrorCodes();
        return $app->json($response, $response->responseType());
      }
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

    $email = $request->get('_email');

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

    //TODO: Remove. Temporary workaround to disable Recaptcha verifications on localhost
    $whitelist = array(
      '127.0.0.1',
      '::1'
    );

    $captcha = $request->get('_recaptcha');
    $user_ip = $request->getClientIp();

    if(!in_array($user_ip, $whitelist)){

      $recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);
      $recaptcha_response = $recaptcha->verify($captcha, $user_ip);

      if(!$recaptcha_response->isSuccess()) {
        $response = new PhpDraftResponse(false, array());
        $response->errors = $recaptcha_response->getErrorCodes();
        return $app->json($response, $response->responseType());
      }
    }

    $user = $app['phpdraft.LoginUserRepository']->Load($email);

    $response = $app['phpdraft.LoginUserService']->BeginForgottenPasswordProcess($user);

    return $app->json($response, $response->responseType());
  }

  public function VerifyResetPasswordToken(Application $app, Request $request) {
    $email = $request->get('_email');
    $verificationToken = $request->get('_verificationToken');

    $validity = $app['phpdraft.LoginUserValidator']->IsResetPasswordTokenValid($email, $verificationToken);

    return $app->json($validity, $validity->responseType());
  }

  public function ResetPassword(Application $app, Request $request) {
    $validity = $app['phpdraft.LoginUserValidator']->IsResetPasswordRequestValid($request);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $email = $request->get('_email');
    $password = $request->get('_password');

    $user = $app['phpdraft.LoginUserRepository']->Load($email);

    $user->password = $password;

    $response = $app['phpdraft.LoginUserService']->ResetPassword($user);

    return $app->json($response, $response->responseType());
  }
}