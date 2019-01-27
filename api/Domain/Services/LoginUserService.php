<?php

namespace PhpDraft\Domain\Services;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Util\StringUtils;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

class LoginUserService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetCurrentUser() {
    $token = $this->app['security.token_storage']->getToken();

    if ($token == null) {
      //In public actions, this isn't an exception - we're just not logged in.
      return null;
      //throw new \Exception("Username not found.");
    }

    $usr = $token->getUser();

    return $this->app['phpdraft.LoginUserRepository']->Load($usr->getUsername());
  }

  //This is a hack to make accessing logged in user info from anonymous routes possible:
  public function GetUserFromHeaderToken(Request $request) {
    $request_token = $request->headers->get(AUTH_KEY_HEADER, '');

    if (empty($request_token)) {
      return null;
    }

    try {
      $decoded = $this->app['security.jwt.encoder']->decode($request_token);

      $email = $decoded->name;

      return $this->app['phpdraft.LoginUserRepository']->Load($email);
    } catch (\Exception $ex) {
      return null;
    }
  }

  public function SetAuthenticationObjectValuesOnLogin(PhpDraftResponse $response, $user) {
    $now = new \DateTime("now", new \DateTimeZone('GMT'));
    $interval = new \DateInterval('P0Y0M0DT0H0M' . AUTH_SECONDS . 'S');
    $authTimeout = $now->add($interval);

    $response->name = $user->getName();
    $response->is_admin = $user->isAdmin();
    $response->token = $this->app['security.jwt.encoder']->encode(['name' => $user->getUsername()]);
    $response->auth_timeout = $authTimeout->format('Y-m-d H:i:s');

    return $response;
  }

  public function SearchCommissioners($searchTerm) {
    $response = new PhpDraftResponse();

    try {
      $response->commissioners = $this->app['phpdraft.LoginUserRepository']->SearchCommissioners($searchTerm);
      $response->success = true;
    } catch (\Exception $ex) {
      $message = $ex->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function GetCommissioner($commish_id) {
    $response = new PhpDraftResponse();

    try {
      $response->commissioner = $this->app['phpdraft.LoginUserRepository']->LoadPublicById($commish_id);
      $response->success = true;
    } catch (\Exception $ex) {
      $message = $ex->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function GetAll() {
    $response = new PhpDraftResponse();

    try {
      $response->users = $this->app['phpdraft.LoginUserRepository']->LoadAll();
      $response->roles = $this->app['phpdraft.LoginUserRepository']->GetRoles();
      $response->success = true;
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function CreateUnverifiedNewUser(LoginUser $user) {
    $user->enabled = false;

    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSaltForUrl();
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->password = $this->app['security.encoder.digest']->encodePassword($user->password, $user->salt);
    $user->roles = array('ROLE_COMMISH');

    $response = new PhpDraftResponse();

    try {
      $user = $this->app['phpdraft.LoginUserRepository']->Create($user);

      $message = new MailMessage();

      $message->to_addresses = array(
        $user->email => $user->name
      );

      $verifyLink = $this->_CreateEmailVerificationLink($user);
      $emailParameters = array(
        'imageBaseUrl' => sprintf("%s/images/email", APP_BASE_URL),
        'verifyLink' => $verifyLink,
      );

      $message->subject = "HootDraft: Verify your email address";
      $message->is_html = true;
      $message->body = $this->app['phpdraft.TemplateRenderService']->RenderTemplate('VerifyEmail.html', $emailParameters);
      $message->altBody = "Hey pal, we need you to verify your email address. Click this link to do so: $verifyLink";

      $this->app['phpdraft.EmailService']->SendMail($message);

      $response->success = true;
    } catch (\Exception $e) {
      //$this->app['db']->rollback();

      $response->success = false;
      $response->errors = array("Unable to create new user or send verification email.");
    }

    return $response;
  }

  public function VerifyUser(LoginUser $user) {
    $user->enabled = true;
    $user->verificationKey = null;

    $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

    return new PhpDraftResponse(true);
  }

  public function BeginForgottenPasswordProcess(LoginUser $user) {
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSaltForUrl();

    $response = new PhpDraftResponse();

    try {
      $this->app['db']->beginTransaction();

      $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

      $message = new MailMessage();

      $message->to_addresses = array(
        $user->email => $user->name
      );

      $resetLink = $this->_CreateForgottenPasswordLink($user);
      $emailParameters = array(
        'imageBaseUrl' => sprintf("%s/images/email", APP_BASE_URL),
        'resetLink' => $resetLink,
      );

      $message->subject = "HootDraft: Reset Password Request";
      $message->is_html = true;
      $message->body = $this->app['phpdraft.TemplateRenderService']->RenderTemplate('ResetPassword.html', $emailParameters);
      $message->altBody = "Hello, looks like you've requested to reset your password. To do so, click this link: $resetLink";

      $this->app['phpdraft.EmailService']->SendMail($message);

      $response->success = true;

      $this->app['db']->commit();
    } catch (\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function ResetPassword(LoginUser $user) {
    $user->verificationKey = null;
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->password = $this->app['security.encoder.digest']->encodePassword($user->password, $user->salt);

    $response = new PhpDraftResponse();

    try {
      $this->app['db']->beginTransaction();

      $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

      $response->success = true;

      $this->app['db']->commit();
    } catch (\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateUserProfile(Request $request) {
    $email = strtolower($request->get('_email'));
    $name = $request->get('_name');
    $newPassword = $request->get('_newPassword');
    $sendEmail = false;
    $invalidateLogin = false;

    $user = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

    $user->name = $name;

    //Update user email, invalidate login
    if (!empty($email) && !StringUtils::equals($email, $user->email)) {
      $user->email = $email;
      $user->enabled = 0;
      $invalidateLogin = true;
      $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
      $sendEmail = true;
    }

    if (!empty($newPassword)) {
      $invalidateLogin = true;
      $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
      $user->password = $this->app['security.encoder.digest']->encodePassword($newPassword, $user->salt);
    }

    $response = new PhpDraftResponse();

    try {
      $this->app['db']->beginTransaction();

      $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

      if ($sendEmail) {
        $message = new MailMessage();

        $message->to_addresses = array(
          $user->email => $user->name
        );

        $verifyLink = $this->_CreateEmailVerificationLink($user);
        $emailParameters = array(
          'imageBaseUrl' => sprintf("%s/images/email", APP_BASE_URL),
          'verifyLink' => $verifyLink,
        );

        $message->subject = "HootDraft: Verify your email address";
        $message->is_html = true;
        $message->body = $this->app['phpdraft.TemplateRenderService']->RenderTemplate('ReverifyEmail.html', $emailParameters);
        $message->altBody = "Hi, and welcome to Hoot Draft! Before we get started, can you click this link to verify that you are who you say you are? Thanks pal! $verifyLink";

        $this->app['phpdraft.EmailService']->SendMail($message);
      }

      $response->success = true;
      $response->invalidateLogin = $invalidateLogin;
      $response->sendEmail = $sendEmail;

      $this->app['db']->commit();
    } catch (\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteUser(LoginUser $user) {
    $response = new PhpDraftResponse();

    try {
      //Find all drafts this user owns
      $drafts = $this->app['phpdraft.DraftRepository']->GetAllDraftsByCommish($user->id);
      foreach ($drafts as $draft) {
        $response = $this->app['phpdraft.DraftService']->DeleteDraft($draft);
        if (!$response->success) {
          throw new \Exception("Unable to recursively delete draft or one of its children.");
        }
      }
      $this->app['phpdraft.LoginUserRepository']->Delete($user);

      $response->success = true;
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function CurrentUserIsAdmin(LoginUser $user) {
    $roles = explode(',', $user->roles);

    return in_array('ROLE_ADMIN', $roles);
  }

  private function _CreateEmailVerificationLink(LoginUser $user) {
    $encodedEmail = urlencode($user->email);
    $encodedToken = urlencode($user->verificationKey);

    return sprintf("%s/verify/%s/%s", APP_BASE_URL, $encodedEmail, $encodedToken);
  }

  private function _CreateForgottenPasswordLink(LoginUser $user) {
    $encodedEmail = urlencode($user->email);
    $encodedToken = urlencode($user->verificationKey);

    return sprintf("%s/resetPassword/%s/%s", APP_BASE_URL, $encodedEmail, $encodedToken);
  }
}
