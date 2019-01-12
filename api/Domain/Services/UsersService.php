<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

class UsersService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function InviteNewUser(LoginUser $user, $message) {
    $response = new PhpDraftResponse();

    $user->password = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->roles = array('ROLE_COMMISH');
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSaltForUrl();

    try {
      $user = $this->app['phpdraft.LoginUserRepository']->Create($user);
      $inviter = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

      $emailParameters = array(
        'imageBaseUrl' => sprintf("%s/images/email", APP_BASE_URL),
        'invitee' => $user->name,
        'inviter' => $inviter->name,
        'message' => $message,
        'setupLink' => $this->GenerateNewInviteLink($user),
      );

      $mailMessage = new MailMessage();

      $mailMessage->to_addresses = array(
        $user->email => $user->name,
      );

      $mailMessage->subject = "$inviter->name invited you to use Hoot Draft!";
      $mailMessage->body = $this->app['phpdraft.TemplateRenderService']->RenderTemplate('UserInvite.html', $emailParameters);
      $mailMessage->is_html = true;

      $this->app['phpdraft.EmailService']->SendMail($mailMessage);

      $response->success = true;
    } catch (\Exception $ex) {
      $exceptionMessage = $ex->getMessage();
      $response->success = false;
      $response->errors[] = $exceptionMessage;
    }

    return $response;
  }

  private function GenerateNewInviteLink(LoginUser $user) {
    $encodedEmail = urlencode($user->email);
    $encodedToken = urlencode($user->verificationKey);

    return sprintf("%s/inviteSetup/%s/%s", APP_BASE_URL, $encodedEmail, $encodedToken);
  }
}
