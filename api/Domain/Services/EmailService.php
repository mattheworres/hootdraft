<?php

namespace PhpDraft\Domain\Services;

use \Silex\Application;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

class EmailService {
  private $app;
  private $mailer;

  public function __construct(Application $app) {
    $this->app = $app;

    $this->mailer = new \PHPMailer();

    $this->mailer->SMTPDebug = 3;                               // Enable verbose debug output

    $this->mailer->isSMTP();
    $this->mailer->Host = MAIL_SERVER;
    $this->mailer->Port = MAIL_PORT;
    //Comment next 4 lines if testing locally with Mailcatcher
    $this->mailer->SMTPAuth = true;
    $this->mailer->Username = MAIL_USER;
    $this->mailer->Password = MAIL_PASS;
    $this->mailer->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted

    $this->mailer->From = MAIL_USER;
    $this->mailer->FromName = 'PHPDraft System';
  }

  public function SendMail(MailMessage $message) {
    foreach($message->to_addresses as $address => $name) {
      $this->mailer->addAddress($address, $name);
    }

    $this->mailer->isHTML($message->is_html);

    $this->mailer->Subject = $message->subject;

    $this->mailer->Body = $message->body;

    if(!$this->mailer->send()) {
      throw new \Exception("Unable to send mail: " . $this->mailer->ErrorInfo);
    }

    return;
  }
}