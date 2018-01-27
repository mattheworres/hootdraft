<?php

namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;

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
    $emailAddress = $request->get('_email');
    $name = $request->get('_name');
    $recaptcha = $request->get('_recaptcha');

    if (empty($password)
      || empty($confirmPassword)
      || empty($emailAddress)
      || empty($name)
      || empty($recaptcha)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    $this->validatePasswordsMatch($password, $confirmPassword, $errors, $valid);

    $this->validatePasswordLength($password, $errors, $valid);

    $this->validateNameLength($name, $errors, $valid);

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    if (!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($name)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    $this->validateUniqueEmail($emailAddress, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsVerificationValid(Request $request) {
    $valid = true;
    $errors = array();

    $emailAddress = $request->get('_email');
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if (strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    if (!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($emailAddress, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function areLoginCredentialsValid($emailAddress, $password) {
    $valid = true;
    $errors = array();

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    $this->validatePasswordLength($password, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsForgottenPasswordUserValid(Request $request) {
    $valid = true;
    $errors = array();

    $emailAddress = $request->get('_email');

    $this->validateEmailExists($emailAddress, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsResetPasswordTokenValid($emailAddress, $verificationToken) {
    $valid = true;
    $errors = array();

    if (empty($emailAddress)
      || empty($verificationToken)) {
      $errors[] = "One or more missing fields";
      $valid = false;
    }

    if (strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if (!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($emailAddress, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    $this->validateEmailExists($emailAddress, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsResetPasswordRequestValid(Request $request) {
    $valid = true;
    $errors = array();

    $emailAddress = $request->get('_email');
    $password = $request->get('_password');
    $confirmPassword = $request->get('_confirmPassword');
    $verificationToken = $this->app['phpdraft.SaltService']->UrlDecodeSalt($request->get('_verificationToken'));

    if (empty($emailAddress)
      || empty($password)
      || empty($confirmPassword)
      || empty($verificationToken)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if (strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if (!$this->app['phpdraft.LoginUserRepository']->VerificationMatches($emailAddress, $verificationToken)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    $this->validatePasswordsMatch($password, $confirmPassword, $errors, $valid);

    $this->validatePasswordLength($password, $errors, $valid);

    $this->validateEmailExists($emailAddress, $errors, $valid);

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsUserProfileUpdateValid(Request $request) {
    $valid = true;
    $errors = array();

    $emailAddress = strtolower($request->get('_email'));
    $name = $request->get('_name');
    $password = $request->get('_password');
    $newPassword = $request->get('_newPassword');
    $newConfirmedPassword = $request->get('_newConfirmedPassword');

    $currentUser = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

    if (empty($currentUser) || $currentUser == null) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with validation.
      return $this->app['phpdraft.ResponseFactory']($valid, $errors);
    }

    //Password required to make any changes
    if (empty($password) || !$this->app['security.encoder.digest']->isPasswordValid($currentUser->password, $password, $currentUser->salt)) {
      $errors[] = "Incorrect password entered.";
      $valid = false;
    }

    //Need to verify new email
    if (empty($emailAddress)) {
      $errors[] = "Email address is missing.";
      $valid = false;
    } else if (!$this->app['phpdraft.StringsEqual']($emailAddress, $currentUser->email)) {
      $this->validateEmailAddress($emailAddress, $errors, $valid);

      $this->validateUniqueEmail($emailAddress, $errors, $valid);
    }

    //Need to verify new password, ensure old password is correct
    if (!empty($newPassword)) {
      $this->validatePasswordLength($newPassword, $errors, $valid);

      $this->validatePasswordsMatch($newPassword, $newConfirmedPassword, $errors, $valid);
    }

    //If the name has changed, ensure the new one is valid and unique
    if ($currentUser->name != $name) {
      if (empty($name)) {
        $errors[] = "Name is required.";
        $valid = false;
      }

      $this->validateNameLength($name, $errors, $valid);

      if (!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($name)) {
        $errors[] = "Name already taken.";
        $valid = false;
      }
    }

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function IsAdminUserUpdateValid(LoginUser $user) {
    $valid = true;
    $errors = array();

    $loadedUser = $this->app['phpdraft.LoginUserRepository']->LoadById($user->id);

    if ($user->id == 0 || empty($loadedUser)) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with vaidation.
      return $this->app['phpdraft.ResponseFactory']($valid, $errors);
    }

    //Need to verify new email
    if (empty($user->email)) {
      $errors[] = "Email address is missing.";
      $valid = false;
    } else if (!$this->app['phpdraft.StringsEqual']($user->email, $loadedUser->email)) {
      $this->validateEmailAddress($user->email, $errors, $valid);

      $this->validateUniqueEmail($user->email, $errors, $valid);
    }

    if (strlen($user->name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }

    if (!$this->app['phpdraft.LoginUserRepository']->NameIsUnique($user->name, $user->id)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  private function validatePasswordsMatch($password1, $password2, &$errors, &$valid) {
    if (!$this->app['phpdraft.StringsEqual']($password1, $password2)) {
      $errors[] = "Password values do not match.";
      $valid = false;
    }
  }

  private function validateUniqueEmail($emailAddress, &$errors, &$valid) {
    if (!$this->app['phpdraft.LoginUserRepository']->EmailIsUnique($emailAddress)) {
      $errors[] = "Email already registered.";
      $valid = false;
    }
  }

  private function validateEmailExists($emailAddress, &$errors, &$valid) {
    if (!$this->app['phpdraft.LoginUserRepository']->EmailExists($emailAddress)) {
      $errors[] = "Email invalid.";
      $valid = false;
    }
  }

  private function validateEmailAddress($emailAddress, &$errors, &$valid) {
    if (!$this->app['phpdraft.EmailValidator']->isValid($emailAddress) || strlen($emailAddress) > 255) {
      $errors[] = "Email invalid.";
      $valid = false;
    }
  }

  private function validatePasswordLength($password, &$errors, &$valid) {
    if (strlen($password) < 8) {
      $errors[] = "Password is below minimum length.";
      $valid = false;
    }

    if (strlen($password) > 255) {
      $errors[] = "Password is above maximum length.";
      $valid = false;
    }
  }

  private function validateNameLength($name, &$errors, &$valid) {
    if (strlen($name) > 100) {
      $errors[] = "Name is above maximum length";
      $valid = false;
    }
  }
}