<?php

namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use Symfony\Component\Security\Core\Util\StringUtils;
use Egulias\EmailValidator\EmailValidator;

class LoginUserValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsRegistrationUserValid(Request $request) {
    $valid = true;
    $errors = array();

    $username = $request->get('_username');
    $password = $request->get('_password');
    $confirmPassword = $request->get('_confirmPassword');
    $email = $request->get('_email');
    $name = $request->get('_name');

    if(empty($username)
      || empty($password)
      || empty($confirmPassword)
      || empty($email)
      || empty($name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if(!StringUtils::equals($password, $confirmPassword)) {
      $errors[] = "Password values do not match.";
      $valid = false;
    }

    if(strlen($username) < 3) {
      $errors[] = "Username is below minimum length.";
      $valid = false;
    }

    if(strlen($username) > 100) {
      $errors[] = "Username is above maximum length.";
      $valid = false;
    }

    if(strlen($password) < 8) {
      $errors[] = "Password is below minimum length.";
      $valid = false;
    }

    if(strlen($password) > 255) {
      $errors[] = "Password is above maximum length.";
      $valid = false;
    }

    if(strlen($email) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    if(strlen($name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }

    $emailValidator = new EmailValidator;

    if (!$emailValidator->isValid($email)) {
      $errors[] = "Email is invalid.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->UsernameIsUnique($username)) {
      $errors[] = "Username already taken.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->EmailIsUnique($email)) {
      $errors[] = "Email already registered.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsVerificationValid(Request $request) {
    $valid = true;
    $errors = array();

    $username = urldecode($request->get('_username'));
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if(strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if(strlen($username) < 3 || strlen($username) > 100) {
      $errors[] = "Username invalid.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($username, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}