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

  public function IsForgottenPasswordUserValid(Request $request) {
    $valid = true;
    $errors = array();

    $username = $request->get('_username');

    if(!$this->app['phpdraft.LoginUserRepository']->UsernameExists($username)) {
      $errors[] = "Username invalid.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsResetPasswordRequestValid(Request $request) {
    $valid = true;
    $errors = array();

    $username = urldecode($request->get('_username'));
    $password = $request->get('_password');
    $confirmPassword = $request->get('_confirmPassword');
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if(empty($username)
      || empty($password)
      || empty($confirmPassword)
      || empty($verificationToken)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

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

    if(!StringUtils::equals($password, $confirmPassword)) {
      $errors[] = "Password values do not match.";
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

    if(!$this->app['phpdraft.LoginUserRepository']->UsernameExists($username)) {
      $errors[] = "Invalid username.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsUserProfileUpdateValid(Request $request) {
    $valid = true;
    $errors = array();

    $id = (int)$request->get('_id');
    $email = strtolower($request->get('_email'));
    $password = $request->get('_password');
    $newPassword = $request->get('_newPassword');
    $newConfirmedPassword = $request->get('_newConfirmedPassword');
    $name = $request->get('_name');

    $user = $this->app['phpdraft.LoginUserRepository']->LoadById($id);
    $currentUser = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

    if(empty($currentUser) || $id == 0 || empty($user) || $user->id != $currentUser->id) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with vaidation.
      return new PhpDraftResponse($valid, $errors);
    }

    //Password required to make any changes
    if(empty($password) || !$this->app['security.encoder.digest']->isPasswordValid($user->password, $password, $user->salt)) {
      $errors[] = "Incorrect password entered.";
      $valid = false;
    }

    //Need to verify new email
    if(!empty($email) && !StringUtils::equals($email, $user->email)) {
      if(strlen($email) > 255) {
        $errors[] = "Email is above maximum length.";
        $valid = false;
      }

      $emailValidator = new EmailValidator;

      if (!$emailValidator->isValid($email)) {
        $errors[] = "Email is invalid.";
        $valid = false;
      }

      if(!$this->app['phpdraft.LoginUserRepository']->EmailIsUnique($email)) {
        $errors[] = "Email already registered.";
        $valid = false;
      }
    }

    //Need to verify new password, ensure old password is correct
    if(!empty($newPassword)) {
      if(strlen($newPassword) < 8) {
        $errors[] = "New password is below minimum length.";
        $valid = false;
      }

      if(strlen($newPassword) > 255) {
        $errors[] = "New password is above maximum length.";
        $valid = false;
      }

      if(!StringUtils::equals($newPassword, $newConfirmedPassword)) {
        $errors[] = "New password values do not match.";
        $valid = false;
      }
    }

    if(strlen($name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}