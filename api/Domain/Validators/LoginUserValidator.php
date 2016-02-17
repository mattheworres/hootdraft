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

  public function isRegistrationUserValid(Request $request) {
    $valid = true;
    $errors = array();

    $password = $request->get('_password');
    $confirmPassword = $request->get('_confirmPassword');
    $email = $request->get('_email');
    $name = $request->get('_name');
    $recaptcha = $request->get('_recaptcha');

    if(empty($password)
      || empty($confirmPassword)
      || empty($email)
      || empty($name)
      || empty($recaptcha)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if(!StringUtils::equals($password, $confirmPassword)) {
      $errors[] = "Password values do not match.";
      $valid = false;
    }

    $this->validatePasswordLength($password, $errors, $valid);

    if(strlen($email) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    $this->validateNameLength($name, $errors, $valid);

    $emailValidator = new EmailValidator;

    if (!$emailValidator->isValid($email)) {
      $errors[] = "Email is invalid.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($name)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    $this->validateUniqueEmail($email, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsVerificationValid(Request $request) {
    $valid = true;
    $errors = array();

    $email = $request->get('_email');
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if(strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $emailValidator = new EmailValidator;

    if(!$emailValidator->isValid($email) || strlen($email) > 255) {
      $errors[] = "Email invalid.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($email, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsLoginUserValid($email, $password) {
    $valid = true;
    $errors = array();

    $emailValidator = new EmailValidator;

    if (!$emailValidator->isValid($email)) {
      $errors[] = "Email is invalid.";
      $valid = false;
    }

    if(strlen($email) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    $this->validatePasswordLength($password, $errors, $valid);
  }

  public function IsForgottenPasswordUserValid(Request $request) {
    $valid = true;
    $errors = array();

    $email = $request->get('_email');

    $this->validateEmailExists($email, $errors, $valid);

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsResetPasswordTokenValid($email, $verificationToken) {
    $valid = true;
    $errors = array();

    if(empty($email)
      || empty($verificationToken)) {
      $errors[] = "One or more missing fields";
      $valid = false;
    }

    if(strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($email, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $emailValidator = new EmailValidator;

    if (!$emailValidator->isValid($email)) {
      $errors[] = "Email is invalid.";
      $valid = false;
    }

    if(strlen($email) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    $this->validateEmailExists($email, $errors, $valid);

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsResetPasswordRequestValid(Request $request) {
    $valid = true;
    $errors = array();

    $email = $request->get('_email');
    $password = $request->get('_password');
    $confirmPassword = $request->get('_confirmPassword');
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if(empty($email)
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

    if(!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($email, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $emailValidator = new EmailValidator;

    if (!$emailValidator->isValid($email)) {
      $errors[] = "Email is invalid.";
      $valid = false;
    }

    if(!StringUtils::equals($password, $confirmPassword)) {
      $errors[] = "Password values do not match.";
      $valid = false;
    }

    if(strlen($email) > 255) {
      $errors[] = "Email is above maximum length.";
      $valid = false;
    }

    $this->validatePasswordLength($password, $errors, $valid);

    $this->validateEmailExists($email, $errors, $valid);

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsUserProfileUpdateValid(Request $request) {
    $valid = true;
    $errors = array();

    $email = strtolower($request->get('_email'));
    $name = $request->get('_name');
    $password = $request->get('_password');
    $newPassword = $request->get('_newPassword');
    $newConfirmedPassword = $request->get('_newConfirmedPassword');

    $currentUser = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

    if(empty($currentUser) || $currentUser == null) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with validation.
      return new PhpDraftResponse($valid, $errors);
    }

    //Password required to make any changes
    if(empty($password) || !$this->app['security.encoder.digest']->isPasswordValid($currentUser->password, $password, $currentUser->salt)) {
      $errors[] = "Incorrect password entered.";
      $valid = false;
    }

    //Need to verify new email
    if(!empty($email) && !StringUtils::equals($email, $currentUser->email)) {
      if(strlen($email) > 255) {
        $errors[] = "Email is above maximum length.";
        $valid = false;
      }

      $emailValidator = new EmailValidator;

      if (!$emailValidator->isValid($email)) {
        $errors[] = "Email is invalid.";
        $valid = false;
      }

      $this->validateUniqueEmail($email, $errors, $valid);
    }

    //Need to verify new password, ensure old password is correct
    if(!empty($newPassword)) {
      $this->validatePasswordLength($newPassword, $errors, $valid);

      if(!StringUtils::equals($newPassword, $newConfirmedPassword)) {
        $errors[] = "New password values do not match.";
        $valid = false;
      }
    }

    //If the name has changed, ensure the new one is valid and unique
    if($currentUser->name != $name) {
      if(empty($name)) {
        $errors[] = "Name is required.";
        $valid = false;
      }

      $this->validateNameLength($name, $errors, $valid);

      if(!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($name)) {
        $errors[] = "Name already taken.";
        $valid = false;
      }
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsAdminUserUpdateValid(LoginUser $user) {
    $valid = true;
    $errors = array();

    $loadedUser = $this->app['phpdraft.LoginUserRepository']->LoadById($user->id);

    if($user->id == 0 || empty($loadedUser)) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with vaidation.
      return new PhpDraftResponse($valid, $errors);
    }

    //Need to verify new email
    if(!empty($user->email) && !StringUtils::equals($user->email, $loadedUser->email)) {
      if(strlen($user->email) > 255) {
        $errors[] = "Email is above maximum length.";
        $valid = false;
      }

      $emailValidator = new EmailValidator;

      if (!$emailValidator->isValid($user->email)) {
        $errors[] = "Email is invalid.";
        $valid = false;
      }

      $this->validateUniqueEmail($email, $errors, $valid);
    }

    if(strlen($user->name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }

    if(!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($user->name, $user->id)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  private function validateUniqueEmail($email, &$errors, &$valid) {
    if(!$this->app['phpdraft.LoginUserRepository']->EmailIsUnique($email)) {
      $errors[] = "Email already registered.";
      $valid = false;
    }
  }

  private function validateEmailExists($email, &$errors, &$valid) {
    if(!$this->app['phpdraft.LoginUserRepository']->EmailExists($email)) {
      $errors[] = "Email invalid.";
      $valid = false;
    }
  }

  private function validatePasswordLength($password, &$errors, &$valid) {
    if(strlen($password) < 8) {
      $errors[] = "Password is below minimum length.";
      $valid = false;
    }

    if(strlen($password) > 255) {
      $errors[] = "Password is above maximum length.";
      $valid = false;
    }
  }

  private function validateNameLength($name, &$errors, &$valid) {
    if(strlen($name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }
  }
}