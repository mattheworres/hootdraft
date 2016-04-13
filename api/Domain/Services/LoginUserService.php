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

    if($token == null) {
      //In public actions, this isn't an exception - we're just not logged in.
      return null;
      //throw new \Exception("Username not found.");
    }

    $usr = $token->getUser();

    return $this->app['phpdraft.LoginUserRepository']->Load($usr->getUsername());
  }

  //This is a hack to make accessing logged in user info from anonymous routes possible:
  public function GetUserFromHeaderToken(Request $request) {
    $request_token = $request->headers->get(AUTH_KEY_HEADER,'');

    if(empty($request_token)) {
      return null;
    }

    try {
      $decoded = $this->app['security.jwt.encoder']->decode($request_token);

      $email = $decoded->name;

      return $this->app['phpdraft.LoginUserRepository']->Load($email);
    }catch(\Exception $ex) {
      return null;
    }
  }

  public function SearchCommissioners($searchTerm) {
    $response = new PhpDraftResponse();

    try {
      $response->commissioners = $this->app['phpdraft.LoginUserRepository']->SearchCommissioners($searchTerm);
      $response->success = true;
    } catch(\Exception $ex) {
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
    } catch(\Exception $ex) {
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
    } catch(\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function CreateUnverifiedNewUser(LoginUser $user) {
    $user->enabled = false;
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->salt = $this->app['phpdraft.SaltService']->GenerateSalt();
    $user->password = $this->app['security.encoder.digest']->encodePassword($user->password, $user->salt);
    $user->roles = array('ROLE_COMMISH');

    $response = new PhpDraftResponse();

    try {
      $user = $this->app['phpdraft.LoginUserRepository']->Create($user);

      $message = new MailMessage();

      $message->to_addresses = array (
        $user->email => $user->name
      );

      $message->subject = "PHPDraft: Verify your email address";
      $message->is_html = true;
      $verificationLink = $this->_CreateEmailVerificationLink($user);
      $message->body = sprintf("The account for the email <strong>%s</strong> was created but the email address must be verified before the account can be activated.<br/><br/>\n\n
        
        Visit this address in your web browser to activate the user:<br/><br/>\n\n

        <a href=\"%s\">%s</a><br/>\n\n
        (For non-HTML enabled email:)<br/>\n
        %s
      ", $user->email, $verificationLink, $verificationLink, $verificationLink);

      $this->app['phpdraft.EmailService']->SendMail($message);

      $response->success = true;
    }catch(\Exception $e) {
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
    $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();

    //Found out that forward slashes are no good for URLs. Go figure.
    while(strpos($user->verificationKey, '/') != 0) {
      $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
    }

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
      $message->body = sprintf("A password recovery request has been made for the account <strong>%s</strong><br/><br/>\n\n
        
        To reset your password, visit the following address in your web browser:<br/><br/>\n\n

        <a href=\"%s\">%s</a><br/>\n
        (For non-HTML enabled email:)<br/>\n
        %s<br/><br/>\n\n

        If you remember your old password, no longer want to change it, or didn't request a password reset - you can ignore this email.
      ", $user->email, $verificationLink, $verificationLink, $verificationLink);

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
    $email = strtolower($request->get('_email'));
    $name = $request->get('_name');
    $newPassword = $request->get('_newPassword');
    $sendEmail = false;
    $invalidateLogin = false;

    $user = $this->app['phpdraft.LoginUserService']->GetCurrentUser();

    $user->name = $name;

    //Update user email, invalidate login
    if(!empty($email) && !StringUtils::equals($email, $user->email)) {
      $user->email = $email;
      $user->enabled = false;
      $invalidateLogin = true;
      $user->verificationKey = $this->app['phpdraft.SaltService']->GenerateSalt();
      $sendEmail = true;
    }

    if(!empty($newPassword)) {
      $invalidateLogin = true;
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
        $message->body = sprintf("The account <strong>%s</strong> was updated but the email address must be verified before the account can be re-activated.<br/><br/>\n\n
          
          Visit this address in your web browser to re-activate the user:<br/><br/>\n\n

          <a href=\"%s\">%s</a><br/>\n
          (For non-HTML enabled email:)<br/>\n
          %s
        ", $user->email, $user->email, $verificationLink, $verificationLink, $verificationLink);

        $this->app['phpdraft.EmailService']->SendMail($message);
      }

      $response->success = true;
      $response->invalidateLogin = $invalidateLogin;
      $response->sendEmail = $sendEmail;

      $this->app['db']->commit();
    }catch(\Exception $e) {
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
      foreach($drafts as $draft) {
        $response = $this->app['phpdraft.DraftService']->DeleteDraft($draft);
        if(!$response->success) {
          throw new \Exception("Unable to recursively delete draft or one of its children.");
        }
      }
      $this->app['phpdraft.LoginUserRepository']->Delete($user);

      $response->success = true;
    } catch(\Exception $e) {
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