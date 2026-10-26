<?php

namespace HootDraft\Domain\Validators;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\SaltService;
use HootDraft\Security\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

// The unauthenticated flows (registration, verification, password reset) plus invite and profile update are
// ported so far. The admin update validator arrives with its controller in 4.6.
class LoginUserValidator {
  /** How long the emailed links stay good. Both kinds share the users.verificationKey column. */
  public const VERIFY_LINK_TTL_SECONDS = 172800; // 48 hours
  public const RESET_LINK_TTL_SECONDS = 3600; // 1 hour

  public function __construct(
    private LoginUserRepository $repository,
    private SaltService $saltService,
    private PasswordHasherFactoryInterface $hasherFactory,
  ) {
  }

  public function IsRegistrationUserValid(string $password, string $confirmPassword, string $emailAddress, string $name, string $recaptcha) {
    $valid = true;
    $errors = array();

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

    if (!$this->repository->NameIsUnique($name)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    $this->validateUniqueEmail($emailAddress, $errors, $valid);

    return new HootDraftResponse($valid, $errors);
  }

  public function IsInviteNewUserValid(LoginUser $user, string $message) {
    $valid = true;
    $errors = array();

    $emailAddress = (string) $user->email;
    $name = (string) $user->name;

    if (empty($emailAddress)
      || empty($name)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    $this->validateNameLength($name, $errors, $valid);

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    if (strlen($message) > 255) {
      $errors[] = "Message too long";
      $valid = false;
    }

    if (!$this->repository->NameIsUnique($name)) {
      $errors[] = "Name already taken.";
      $valid = false;
    }

    $this->validateUniqueEmail($emailAddress, $errors, $valid);

    return new HootDraftResponse($valid, $errors);
  }

  /** @param ?LoginUser $currentUser The signed-in user as stored in the database (hash and salt included) */
  public function IsUserProfileUpdateValid(?LoginUser $currentUser, string $emailAddress, string $name, string $password, string $newPassword, string $newConfirmedPassword) {
    $valid = true;
    $errors = array();

    $emailAddress = strtolower($emailAddress);

    if (empty($currentUser)) {
      $valid = false;
      $errors[] = "Invalid user.";

      //Because we need to compare new & old values, we need a valid user record to proceed with validation.
      return new HootDraftResponse($valid, $errors);
    }

    //Password required to make any changes
    if (empty($password) || !$this->passwordMatches($currentUser, $password)) {
      $errors[] = "Incorrect password entered.";
      $valid = false;
    }

    //Need to verify new email
    if (empty($emailAddress)) {
      $errors[] = "Email address is missing.";
      $valid = false;
    } else if (!hash_equals($emailAddress, strtolower((string) $currentUser->email))) {
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

      // Names compare case-insensitively, so leave this user's own row out or a change of capitalization conflicts with itself
      if (!$this->repository->NameIsUnique($name, $currentUser->id)) {
        $errors[] = "Name already taken.";
        $valid = false;
      }
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsVerificationValid(string $emailAddress, string $rawVerificationToken) {
    $valid = true;
    $errors = array();

    $verificationToken = $this->saltService->UrlDecodeSalt($rawVerificationToken);

    if (strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);

    if (!$this->repository->VerificationMatches($emailAddress, $verificationToken, self::VERIFY_LINK_TTL_SECONDS)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsForgottenPasswordUserValid(string $emailAddress) {
    $valid = true;
    $errors = array();

    $this->validateEmailExists($emailAddress, $errors, $valid);

    return new HootDraftResponse($valid, $errors);
  }

  public function IsResetPasswordTokenValid(string $emailAddress, string $rawVerificationToken) {
    $valid = true;
    $errors = array();

    $verificationToken = $this->saltService->UrlDecodeSalt($rawVerificationToken);

    if (empty($emailAddress)
      || empty($verificationToken)) {
      $errors[] = "One or more missing fields";
      $valid = false;
    }

    if (strlen($verificationToken) != 16) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    if (!$this->repository->VerificationMatches($emailAddress, $verificationToken, self::RESET_LINK_TTL_SECONDS)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);
    $this->validateEmailExists($emailAddress, $errors, $valid);
    $this->validateResetAllowed($emailAddress, $errors, $valid);

    return new HootDraftResponse($valid, $errors);
  }

  public function IsResetPasswordRequestValid(string $emailAddress, string $password, string $confirmPassword, string $rawVerificationToken) {
    $valid = true;
    $errors = array();

    $verificationToken = $this->saltService->UrlDecodeSalt($rawVerificationToken);

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

    if (!$this->repository->VerificationMatches($emailAddress, $verificationToken, self::RESET_LINK_TTL_SECONDS)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }

    $this->validateEmailAddress($emailAddress, $errors, $valid);
    $this->validatePasswordsMatch($password, $confirmPassword, $errors, $valid);
    $this->validatePasswordLength($password, $errors, $valid);
    $this->validateEmailExists($emailAddress, $errors, $valid);
    $this->validateResetAllowed($emailAddress, $errors, $valid);

    return new HootDraftResponse($valid, $errors);
  }

  /** True when the plain password matches the one stored for this user, whichever hash it is still on. */
  private function passwordMatches(LoginUser $user, string $plainPassword): bool {
    return $this->hasherFactory->getPasswordHasher(User::class)
      ->verify((string) $user->password, $plainPassword, $user->salt === '' ? null : $user->salt);
  }

  private function validatePasswordsMatch($password1, $password2, &$errors, &$valid) {
    if (!hash_equals((string) $password1, (string) $password2)) {
      $errors[] = "Password values do not match.";
      $valid = false;
    }
  }

  private function validateUniqueEmail($emailAddress, &$errors, &$valid) {
    if (!$this->repository->EmailIsUnique($emailAddress)) {
      $errors[] = "Email already registered.";
      $valid = false;
    }
  }

  private function validateEmailExists($emailAddress, &$errors, &$valid) {
    if (!$this->repository->EmailExists($emailAddress)) {
      $errors[] = "Email invalid.";
      $valid = false;
    }
  }

  // A reset link only works for verified accounts: it must not stand in for verifying the address, or lift a disable
  private function validateResetAllowed($emailAddress, &$errors, &$valid) {
    if (!$this->repository->IsEnabled($emailAddress)) {
      $errors[] = "Verification token invalid.";
      $valid = false;
    }
  }

  private function validateEmailAddress($emailAddress, &$errors, &$valid) {
    if (!(new EmailValidator())->isValid($emailAddress, new RFCValidation()) || strlen($emailAddress) > 255) {
      $errors[] = "Email invalid.";
      $valid = false;
    }
  }

  private function validatePasswordLength($password, &$errors, &$valid) {
    if (strlen($password) < 8) {
      $errors[] = "Password is below minimum length.";
      $valid = false;
    }

    // bcrypt only uses the first 72 bytes; refuse longer passwords rather than silently truncating them
    if (strlen($password) > 72) {
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
