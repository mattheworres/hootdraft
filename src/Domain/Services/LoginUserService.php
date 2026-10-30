<?php

namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\EmailVerificationResend;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Validators\LoginUserValidator;
use HootDraft\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

// Token minting is gone: the session cookie replaced the JWT.
class LoginUserService {
  public function __construct(
    private LoginUserRepository $repository,
    private \PDO $pdo,
    private EmailService $emailService,
    private TemplateRenderService $templates,
    private SaltService $saltService,
    private LoggerInterface $logger,
    private PasswordHasherFactoryInterface $hasherFactory,
    private DraftRepository $drafts,
    private DraftService $draftService,
    #[Autowire('%hootdraft.app_base_url%')] private string $appBaseUrl,
  ) {
  }

  public function GetAll() {
    $response = new HootDraftResponse();

    try {
      $response->users = $this->repository->LoadAll();
      $response->roles = $this->repository->GetRoles();
      $response->success = true;
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  /** Deletes the user and every draft they own as one unit: any failure leaves the user and all their drafts intact. */
  public function DeleteUser(LoginUser $user) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      //Find all drafts this user owns
      $drafts = $this->drafts->GetAllDraftsByCommish($user->id);
      foreach ($drafts as $draft) {
        $deleted = $this->draftService->DeleteDraft($draft);
        if (!$deleted->success) {
          $this->logger->error("Unable to delete draft $draft->draft_id while deleting user $user->id: " . implode('; ', $deleted->errors));

          throw new \Exception("Unable to recursively delete draft or one of its children.");
        }
      }
      $this->repository->Delete($user);

      $this->pdo->commit();
      $this->drafts->FlushPendingCacheResets();

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();
      $this->drafts->FlushPendingCacheResets();

      $response->success = false;
      $response->errors[] = $e->getMessage();
    }

    return $response;
  }

  public function IsAdmin(LoginUser $user) {
    return in_array('ROLE_ADMIN', LoginUser::parseRoles($user->roles), true);
  }

  public function SearchCommissioners($searchTerm) {
    $response = new HootDraftResponse();

    try {
      $response->commissioners = $this->repository->SearchCommissioners($searchTerm);
      $response->success = true;
    } catch (\Exception $ex) {
      $response->success = false;
      $response->errors[] = $ex->getMessage();
    }

    return $response;
  }

  public function GetCommissioner($commish_id) {
    $response = new HootDraftResponse();

    try {
      $response->commissioner = $this->repository->LoadPublicById($commish_id);
      $response->success = true;
    } catch (\Exception $ex) {
      $response->success = false;
      $response->errors[] = $ex->getMessage();
    }

    return $response;
  }

  public function CreateEmailUnverifiedNewUser(LoginUser $user) {
    $user->enabled = true;
    $user->emailVerified = false;

    $this->issueEmailVerificationKey($user);
    $user->salt = ''; // only the legacy digest hasher used a per-user salt
    $user->password = $this->hash($user->password);
    $user->roles = array('ROLE_COMMISH');

    $response = new HootDraftResponse();

    try {
      // The account only sticks if the email goes out: otherwise the name and email would be taken by somebody
      // who never got a link and can't register again.
      $this->pdo->beginTransaction();

      $user = $this->repository->Create($user);

      $this->sendEmailVerificationEmail($user);

      $this->pdo->commit();

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();
      $this->logger->error('Unable to create new user or send the Email Verification Key link: ' . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors = array("Unable to create new user or send the email to verify your address.");
    }

    return $response;
  }

  /** The user followed the emailed link. Their address is Email Verified now; `enabled` is the Admin's and stays as it is. */
  public function MarkEmailVerified(LoginUser $user) {
    $user->emailVerified = true;
    $this->clearEmailVerificationKey($user);

    $this->repository->Update($user);

    return new HootDraftResponse(true);
  }

  /**
   * For an Enabled, Email Unverified user who gave the right password: if their Email Verification Key has expired
   * (or is missing), issues a new one and emails the link. A fresh key sends nothing: the link they already have
   * still works, and this is what keeps the email volume down. The new key and the email are one unit: if the email
   * fails, the old key stays, so the user is not locked out for 48 hours and the next login tries again.
   */
  public function ResendEmailVerificationIfExpired(LoginUser $user): EmailVerificationResend {
    if (!$user->enabled || $user->emailVerified || !$this->emailVerificationKeyExpired($user)) {
      return EmailVerificationResend::NotNeeded;
    }

    $this->issueEmailVerificationKey($user);

    try {
      $this->pdo->beginTransaction();

      $this->repository->Update($user);

      $this->sendEmailVerificationEmail($user);

      $this->pdo->commit();

      return EmailVerificationResend::Sent;
    } catch (\Exception $e) {
      $this->rollBack();
      $this->logger->error('Unable to send a new Email Verification Key link: ' . $e->getMessage(), ['exception' => $e]);

      return EmailVerificationResend::Failed;
    }
  }

  public function BeginForgottenPasswordProcess(LoginUser $user) {
    $response = new HootDraftResponse();

    // A Reset must not stand in for Email Verification, and a Disabled user stays out
    if (!$user->enabled || !$user->emailVerified) {
      $response->success = false;
      $response->errors = array("This account's email address is not verified yet. Check your email for the link to verify it.");

      return $response;
    }

    $this->issueResetKey($user);

    try {
      $this->pdo->beginTransaction();

      $user = $this->repository->Update($user);

      $message = new MailMessage();
      $message->to_addresses = array($user->email => $user->name);

      $resetLink = $this->createForgottenPasswordLink($user);

      $message->subject = "HootDraft: Reset Password Request";
      $message->is_html = true;
      $message->body = $this->templates->RenderTemplate('ResetPassword.html', array(
        'imageBaseUrl' => sprintf("%s/images/email", $this->appBaseUrl),
        'resetLink' => $resetLink,
      ));
      $message->altBody = "Hello, looks like you've requested to reset your password. To do so, click this link: $resetLink";

      $this->emailService->SendMail($message);

      $response->success = true;

      $this->pdo->commit();
    } catch (\Exception $e) {
      $this->rollBack();
      $this->logger->error('Unable to send password reset email: ' . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors = array("Unable to send the password reset email.");
    }

    return $response;
  }

  public function ResetPassword(LoginUser $user) {
    $this->clearResetKey($user);
    $user->salt = '';
    $user->password = $this->hash($user->password);

    $response = new HootDraftResponse();

    try {
      $this->repository->Update($user);

      $response->success = true;
    } catch (\Exception $e) {
      $this->logger->error('Unable to reset password: ' . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors = array("Unable to reset the password.");
    }

    return $response;
  }

  /**
   * Saves the signed-in commissioner's profile. A new email address or password ends their login: the session is
   * dropped on the next request because the user no longer matches the database. A new address also has to be
   * Email Verified again, so the user cannot log in until the emailed link is followed. `enabled` is not touched.
   */
  public function UpdateUserProfile(LoginUser $user, string $email, string $name, string $newPassword) {
    $email = strtolower($email);
    $sendEmail = false;
    $invalidateLogin = false;

    $user->name = $name;

    //Update user email, invalidate login
    if (!empty($email) && !hash_equals($email, strtolower((string) $user->email))) {
      $user->email = $email;
      $user->emailVerified = false;
      $invalidateLogin = true;
      $this->issueEmailVerificationKey($user);
      $sendEmail = true;
    }

    if (!empty($newPassword)) {
      $invalidateLogin = true;
      $user->salt = '';
      $user->password = $this->hash($newPassword);
    }

    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      $user = $this->repository->Update($user);

      if ($sendEmail) {
        $this->sendEmailVerificationEmail($user, true);
      }

      $this->pdo->commit();

      $response->success = true;
      $response->invalidateLogin = $invalidateLogin;
      $response->sendEmail = $sendEmail;
    } catch (\Exception $e) {
      $this->rollBack();
      $this->logger->error('Unable to update user profile: ' . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors = array("Unable to update the profile.");
    }

    return $response;
  }

  private function issueEmailVerificationKey(LoginUser $user) {
    $user->emailVerificationKey = $this->saltService->GenerateSaltForUrl();
    $user->emailVerificationKeyCreated = gmdate('Y-m-d H:i:s');
  }

  private function clearEmailVerificationKey(LoginUser $user) {
    $user->emailVerificationKey = null;
    $user->emailVerificationKeyCreated = null;
  }

  private function emailVerificationKeyExpired(LoginUser $user): bool {
    if (empty($user->emailVerificationKey) || empty($user->emailVerificationKeyCreated)) {
      return true;
    }

    $created = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $user->emailVerificationKeyCreated, new \DateTimeZone('UTC'));

    return $created === false || $created->getTimestamp() <= time() - LoginUserValidator::EMAIL_VERIFICATION_KEY_TTL_SECONDS;
  }

  /** @param bool $addressChanged True when the user changed their address (not a new user, not a resend) */
  private function sendEmailVerificationEmail(LoginUser $user, bool $addressChanged = false) {
    $message = new MailMessage();
    $message->to_addresses = array($user->email => $user->name);

    $emailVerificationLink = $this->createEmailVerificationLink($user);

    $message->subject = "HootDraft: Please verify your email address";
    $message->is_html = true;
    $message->body = $this->templates->RenderTemplate($addressChanged ? 'EmailVerificationAddressChanged.html' : 'EmailVerification.html', array(
      'imageBaseUrl' => sprintf("%s/images/email", $this->appBaseUrl),
      'emailVerificationLink' => $emailVerificationLink,
    ));
    $message->altBody = $addressChanged
      ? "Hey pal, we need you to verify your new email address. Click this link to do so: $emailVerificationLink"
      : "Hi, and welcome to Hoot Draft! Before we get started, can you click this link to verify your email address? Thanks pal! $emailVerificationLink";

    $this->emailService->SendMail($message);
  }

  private function issueResetKey(LoginUser $user) {
    $user->resetKey = $this->saltService->GenerateSaltForUrl();
    $user->resetKeyCreated = gmdate('Y-m-d H:i:s');
  }

  private function clearResetKey(LoginUser $user) {
    $user->resetKey = null;
    $user->resetKeyCreated = null;
  }

  private function rollBack() {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }
  }

  private function hash(string $plainPassword): string {
    return $this->hasherFactory->getPasswordHasher(User::class)->hash($plainPassword);
  }

  private function createEmailVerificationLink(LoginUser $user) {
    return sprintf("%s/verify/%s/%s", $this->appBaseUrl, urlencode($user->email), urlencode($user->emailVerificationKey));
  }

  private function createForgottenPasswordLink(LoginUser $user) {
    return sprintf("%s/resetPassword/%s/%s", $this->appBaseUrl, urlencode($user->email), urlencode($user->resetKey));
  }
}
