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
    $token = $this->app['security']->getToken();

    if($token == null) {
      throw new Exception("Username not found.");
    }

    $usr = $token->getUser();

    return $this->app['phpdraft.LoginUserRepository']->Load($usr->getUsername());
  }

  public function CreateUnverifiedNewUser(LoginUser $user) {
    $user->enabled = false;
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->password = $this->app['security.encoder.digest']->encodePassword($user->password, $user->salt);
    $user->roles = array('ROLE_MANAGER');

    $response = new PhpDraftResponse();

    try {
      $this->app['db']->beginTransaction();

      $user = $this->app['phpdraft.LoginUserRepository']->Create($user);

      $message = new MailMessage();

      $message->to_addresses = array (
        $user->email => $user->name
      );

      $message->subject = "PHPDraft: Verify your email address";
      $message->is_html = true;
      $verificationLink = $this->_CreateEmailVerificationLink($user);
      $message->body = sprintf("The username <strong>%s</strong> was created but needs the associated email address <strong>%s</strong> verified before the account can be activated.<br/><br/>\n\n
        
        Visit this address in your web browser to activate the user:<br/><br/>\n\n

        <a href=\"%s\">%s</a><br/>\n
        (For non-HTML enabled email:)<br/>\n
        %s
      ", $user->username, $user->email, $verificationLink, $verificationLink, $verificationLink);

      $this->app['phpdraft.EmailService']->SendMail($message);

      $response->success = true;

      $this->app['db']->commit();
    }catch(\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
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
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();

    $response = new PhpDraftResponse();

    try {
      $this->app['db']->beginTransaction();  

      $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

      $message = new MailMessage();

      $message->to_addresses = array (
        $user->email => $user->name
      );

      $message->subject = "PHPDraft: Reset Password Request";
      $message->is_html = true;
      $verificationLink = $this->_CreateForgottenPasswordLink($user);
      $message->body = sprintf("A password recovery request has been made for the username <strong>%s</strong><br/><br/>\n\n
        
        To reset your password, visit the following address in your web browser:<br/><br/>\n\n

        <a href=\"%s\">%s</a><br/>\n
        (For non-HTML enabled email:)<br/>\n
        %s<br/><br/>\n\n

        If you remember your old password, no longer want to change it, or didn't request a password reset - you can ignore this email.
      ", $user->username, $verificationLink, $verificationLink, $verificationLink);

      $this->app['phpdraft.EmailService']->SendMail($message);

      $response->success = true;

      $this->app['db']->commit();
    }catch(\Exception $e) {
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
    }catch(\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateUserProfile(Request $request) {
    $id = $request->get('_id');
    $email = strtolower($request->get('_email'));
    $name = $request->get('_name');
    $password = $request->get('_password');
    $newPassword = $request->get('_newPassword');
    $sendEmail = false;

    $user = $this->app['phpdraft.LoginUserRepository']->LoadById($id);

    $user->name = $name;

    //Update user email, invalidate login
    if(!empty($email) && !StringUtils::equals($email, $user->email)) {
      $user->email = $email;
      $user->enabled = false;
      $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
      $sendEmail = true;
    }

    if(!empty($newPassword)) {
      $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
      $user->password = $this->app['security.encoder.digest']->encodePassword($newPassword, $user->salt);
    }

    $response = new PhpDraftResponse();

    try{
      $this->app['db']->beginTransaction();

      $user = $this->app['phpdraft.LoginUserRepository']->Update($user);

      if($sendEmail) {
        $message = new MailMessage();

        $message->to_addresses = array (
          $user->email => $user->name
        );

        $message->subject = "PHPDraft: Verify your email address";
        $message->is_html = true;
        $verificationLink = $this->_CreateEmailVerificationLink($user);
        $message->body = sprintf("The username <strong>%s</strong> was updated but needs the associated email address <strong>%s</strong> verified before the account can be re-activated.<br/><br/>\n\n
          
          Visit this address in your web browser to re-activate the user:<br/><br/>\n\n

          <a href=\"%s\">%s</a><br/>\n
          (For non-HTML enabled email:)<br/>\n
          %s
        ", $user->username, $user->email, $verificationLink, $verificationLink, $verificationLink);

        $this->app['phpdraft.EmailService']->SendMail($message);
      }

      $response->success = true;

      $this->app['db']->commit();
    }catch(\Exception $e) {
      $this->app['db']->rollback();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  private function _CreateEmailVerificationLink(LoginUser $user) {
    $encodedUsername = urlencode($user->username);
    $encodedToken = urlencode($user->verificationKey);

    return sprintf("%s/verify?_username=%s&_verificationToken=%s", $this->app['phpdraft.appBaseUrl'], $encodedUsername, $encodedToken);
  }

  private function _CreateForgottenPasswordLink(LoginUser $user) {
    $encodedUsername = urlencode($user->username);
    $encodedToken = urlencode($user->verificationKey);

    return sprintf("%s/resetPassword?_username=%s&_verificationToken=%s", $this->app['phpdraft.appBaseUrl'], $encodedUsername, $encodedToken);
  }
}