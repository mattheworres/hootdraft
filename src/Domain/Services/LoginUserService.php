<?php

namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

// Only the account lifecycle needed by authentication is ported so far (create, verify, forgotten password,
// reset). Token minting is gone: the session cookie replaced the JWT. The rest arrives in step 4.
class LoginUserService {
  public function __construct(
    private LoginUserRepository $repository,
    private \PDO $pdo,
    private EmailService $emailService,
    private TemplateRenderService $templates,
    private SaltService $saltService,
    private LoggerInterface $logger,
    private PasswordHasherFactoryInterface $hasherFactory,
    #[Autowire('%hootdraft.app_base_url%')] private string $appBaseUrl,
  ) {
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
