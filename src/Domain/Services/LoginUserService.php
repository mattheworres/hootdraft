<?php

namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\LoginUserRepository;
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

  public function CreateUnverifiedNewUser(LoginUser $user) {
    $user->enabled = false;

    $this->issueVerificationKey($user);
    $user->salt = ''; // only the legacy digest hasher used a per-user salt
    $user->password = $this->hash($user->password);
    $user->roles = array('ROLE_COMMISH');

    $response = new HootDraftResponse();

    try {
      // The account only sticks if the email goes out: otherwise the name and email would be taken by somebody
      // who never got a link and can't register again.
      $this->pdo->beginTransaction();

      $user = $this->repository->Create($user);

      $message = new MailMessage();
      $message->to_addresses = array($user->email => $user->name);

      $verifyLink = $this->createEmailVerificationLink($user);

      $message->subject = "HootDraft: Verify your email address";
      $message->is_html = true;
      $message->body = $this->templates->RenderTemplate('VerifyEmail.html', array(
        'imageBaseUrl' => sprintf("%s/images/email", $this->appBaseUrl),
        'verifyLink' => $verifyLink,
      ));
      $message->altBody = "Hey pal, we need you to verify your email address. Click this link to do so: $verifyLink";

      $this->emailService->SendMail($message);

      $this->pdo->commit();

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();
      $this->logger->error('Unable to create new user or send verification email: ' . $e->getMessage(), ['exception' => $e]);

      $response->success = false;
      $response->errors = array("Unable to create new user or send verification email.");
    }

    return $response;
  }

  public function VerifyUser(LoginUser $user) {
    $user->enabled = true;
    $this->clearVerificationKey($user);

    $this->repository->Update($user);

    return new HootDraftResponse(true);
  }

  public function BeginForgottenPasswordProcess(LoginUser $user) {
    $response = new HootDraftResponse();

    // An unverified (or disabled) account's key is its verification key; replacing it would break the link
    // they were already sent, and a reset must not stand in for verifying the address.
    if (!$user->enabled) {
      $response->success = false;
      $response->errors = array("This account hasn't been verified yet. Check your email for the verification link.");

      return $response;
    }

    $this->issueVerificationKey($user);

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
    $this->clearVerificationKey($user);
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
   * verified again, so the account is disabled until the emailed link is followed.
   */
  public function UpdateUserProfile(LoginUser $user, string $email, string $name, string $newPassword) {
    $email = strtolower($email);
    $sendEmail = false;
    $invalidateLogin = false;

    $user->name = $name;

    //Update user email, invalidate login
    if (!empty($email) && !hash_equals($email, strtolower((string) $user->email))) {
      $user->email = $email;
      $user->enabled = 0;
      $invalidateLogin = true;
      $this->issueVerificationKey($user);
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
        $message = new MailMessage();

        $message->to_addresses = array(
          $user->email => $user->name
        );

        $verifyLink = $this->createEmailVerificationLink($user);

        $message->subject = "HootDraft: Verify your email address";
        $message->is_html = true;
        $message->body = $this->templates->RenderTemplate('ReverifyEmail.html', array(
          'imageBaseUrl' => sprintf("%s/images/email", $this->appBaseUrl),
          'verifyLink' => $verifyLink,
        ));
        $message->altBody = "Hi, and welcome to Hoot Draft! Before we get started, can you click this link to verify that you are who you say you are? Thanks pal! $verifyLink";

        $this->emailService->SendMail($message);
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

  private function issueVerificationKey(LoginUser $user) {
    $user->verificationKey = $this->saltService->GenerateSaltForUrl();
    $user->verificationKeyCreated = gmdate('Y-m-d H:i:s');
  }

  private function clearVerificationKey(LoginUser $user) {
    $user->verificationKey = null;
    $user->verificationKeyCreated = null;
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
    return sprintf("%s/verify/%s/%s", $this->appBaseUrl, urlencode($user->email), urlencode($user->verificationKey));
  }

  private function createForgottenPasswordLink(LoginUser $user) {
    return sprintf("%s/resetPassword/%s/%s", $this->appBaseUrl, urlencode($user->email), urlencode($user->verificationKey));
  }
}
