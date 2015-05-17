<?php

namespace PhpDraft\Domain\Services;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Entities\PhpDraftResponse;

class LoginUserService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function CreateUnverifiedNewUser(LoginUser $user) {
    $user->enabled = false;
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->password = $this->app['security.encoder.digest']->encodePassword($user->password, $user->salt);
    $user->roles = array('ROLE_MANAGER');

    $response = new PhpDraftResponse();  

    try {
      $user = $this->app['phpdraft.LoginUserRepository']->Create($user);

      //TODO: Send verification email to xyz

      $response->success = true;
    }catch(Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function VerifyUser(LoginUser $user) {
    $user->enabled = true;

    //TODO: Implement update!
    $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

    $response = new PhpDraftResponse();

    $response->success = true;

    return $response;
  }
}