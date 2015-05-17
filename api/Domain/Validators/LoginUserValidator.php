<?php

namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Entities\PhpDraftResponse;
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

    if(empty($request['_username'])
      || empty($request['_password'])
      || empty($request['_confirmPassword'])
      || empty($request['_email'])
      || !StringUtils::equals($request['_password'], $request['_confirmPassword'])) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if(strlen($request['_username']) < 3) {
      $errors[] = "Username is below minimum length.";
      $valid = false;
    }

    if(strlen($request['_username']) > 100) {
      $errors[] = "Username is above maximum length.";
      $valid = false;
    }

    if(strlen($request['_password']) < 8) {
      $errors[] = "Password is below minimum length.";
      $valid = false;
    }

    if(strlen($request['_password']) > 255) {
      $errors[] = "Password is above maximum length.";
      $valid = false;
    }

    if(strlen($request['_email']) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    $validator = new EmailValidator;

    if (!$validator->isValid($request['_email'])) {
      $errors[] "Email is invalid.";
      $valid = false;
    }

    $username_stmt = $this->app['db']->prepare("SELECT username FROM users WHERE username = ? LIMIT 1");
    $username_stmt->bindParam(1, strtolower($request['_username']));

    if (!$username_stmt->execute()) {
      throw new Exception(sprintf('Username "%s" is invalid', $request['_username']));
    }

    if($username_stmt->rowCount() != 0) {
      $errors[] = "Username already taken.";
      $valid = false;
    }

    $email_stmt = $this->app['db']->prepare("SELECT email FROM users WHERE email = ? LIMIT 1");
    $email_stmt->bindParam(1, strtolower($request['_email']));

    if(!$email_stmt->execute()) {
      throw new Exception(sprintf('Email %s is invalid', $request['_email']));
    }

    if($username_stmt->rowCount() != 0) {
      $errors[] = "Email already registered.";
      $valid = false;
    }

    $response = new PhpDraftResponse();

    $response->success = $valid;
    $response->errors = $errors;

    return $response;
  }

  public function IsVerificationValid(Request $request) {
    $valid = true;
    $errors = array();

    if(strlen($request['_verificationToken']) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if(strlen($request['_username']) < 3 || strlen($request['_username']) > 100) {
      $errors[] = "Username invalid.";
      $valid = false;
    }

    $username_stmt = $this->app['db']->prepare("SELECT username, verifiationKey FROM users WHERE username = ? AND verificationKey = ? LIMIT 1");
    $username_stmt->bindParam(1, strtolower($request['_username']));
    $username_stmt->bindParam(2, $request['_verificationToken']);

    if(!$username_stmt->execute()) {
      $errors[] = "Verification invalid.";
      $valid = false;
    }

    if($username_stmt->rowCount() != 0) {
      $errors[] = "Verification invalid.";
      $valid = false;
    }

    $response = new PhpDraftResponse();

    $response->success = $valid;
    $response->errors = $errors;

    return $response;
  }
}