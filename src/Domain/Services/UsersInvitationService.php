<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Validators\LoginUserValidator;
use HootDraft\Security\CurrentUser;
use HootDraft\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UsersInvitationService {
  public function __construct(
    private LoginUserRepository $repository,
    private \PDO $pdo,
    private EmailService $emailService,
    private TemplateRenderService $templates,
    private SaltService $saltService,
    private LoggerInterface $logger,
    private PasswordHasherFactoryInterface $hasherFactory,
    private CurrentUser $currentUser,
    #[Autowire('%hootdraft.app_base_url%')] private string $appBaseUrl,
  ) {
  }

  /**
   * Creates an account for the invitee and emails them a link to choose their password (the client's
   * /inviteSetup page uses the password reset endpoints).
   *
   * The account is created enabled, with a password nobody knows: the reset endpoints refuse disabled accounts,
   * so a disabled one could never be set up. Following the emailed link is what proves the address.
   */
  public function InviteNewUser(LoginUser $user, $message) {
    $response = new HootDraftResponse();

    $user->password = $this->hasherFactory->getPasswordHasher(User::class)->hash(bin2hex(random_bytes(32)));
    $user->salt = '';
    $user->enabled = 1;
    $user->roles = array('ROLE_COMMISH');
    $user->resetKey = $this->saltService->GenerateSaltForUrl();
    // The link is checked against the (1 hour) password reset lifetime, but an invitation should last as long as
    // a verification link, so the key is dated ahead by the difference
    $user->resetKeyCreated = gmdate('Y-m-d H:i:s', time() + LoginUserValidator::VERIFY_LINK_TTL_SECONDS - LoginUserValidator::RESET_LINK_TTL_SECONDS);

    try {
      // The invitation only sticks if the email goes out, otherwise the name and email would be taken for nothing
      $this->pdo->beginTransaction();

      $user = $this->repository->Create($user);
      $inviter = $this->currentUser->get();

      $setupLink = $this->generateNewInviteLink($user);
      $emailParameters = array(
        'imageBaseUrl' => sprintf("%s/images/email", $this->appBaseUrl),
        'invitee' => $user->name,
        'inviter' => $inviter->getName(),
        'message' => $message,
        'setupLink' => $setupLink,
      );

      $mailMessage = new MailMessage();

      $mailMessage->to_addresses = array(
        $user->email => $user->name,
      );

      $mailMessage->subject = "{$inviter->getName()} invited you to use Hoot Draft!";
      $mailMessage->body = $this->templates->RenderTemplate('UserInvite.html', $emailParameters);
      $mailMessage->altBody = "Hello partner! {$inviter->getName()} invited you to use Hoot Draft, a whizz banger of an app that makes offline fantasy drafts a real hoot! Setup your new account here: $setupLink";
      $mailMessage->is_html = true;

      $this->emailService->SendMail($mailMessage);

      $this->pdo->commit();

      $response->success = true;
    } catch (\Exception $ex) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      $this->logger->error('Unable to invite new user: ' . $ex->getMessage(), ['exception' => $ex]);

      $response->success = false;
      $response->errors[] = "Unable to create the invited user or send the invitation email.";
    }

    return $response;
  }

  private function generateNewInviteLink(LoginUser $user) {
    $encodedEmail = urlencode($user->email);
    $encodedToken = urlencode($user->resetKey);

    return sprintf("%s/inviteSetup/%s/%s", $this->appBaseUrl, $encodedEmail, $encodedToken);
  }
}
