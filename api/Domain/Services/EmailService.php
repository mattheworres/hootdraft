<?php

namespace PhpDraft\Domain\Services;

use \Silex\Application;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService {
  private $app;
  private $mailer;

  public function __construct(Application $app) {
    $this->app = $app;

    $this->mailer = new PHPMailer();

    //Uncomment this line to help debug issues with your SMTP server
    //Watch the response from the API when you register/start lost pwd to see the output.
    //$this->mailer->SMTPDebug = 2;                               // Enable verbose debug output

    $this->mailer->isSMTP();
    $this->mailer->Host = MAIL_SERVER;
    $this->mailer->Port = MAIL_PORT;

    if (MAIL_DEVELOPMENT != true) {
      $this->mailer->SMTPAuth = true;
      $this->mailer->Username = MAIL_USER;
      $this->mailer->Password = MAIL_PASS;

      if (MAIL_USE_ENCRYPTION == true) {
        $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
      }
    }

    $this->mailer->From = MAIL_USER;
    $this->mailer->FromName = 'PHPDraft System';
  }

  public function SendMail(MailMessage $message) {
    foreach ($message->to_addresses as $address => $name) {
      $this->mailer->addAddress($address, $name);
    }

    $this->mailer->isHTML($message->is_html);

    $this->mailer->Subject = $message->subject;

    $this->mailer->Body = $message->body;

    if (!$this->mailer->send()) {
      throw new \Exception("Unable to send mail: " . $this->mailer->ErrorInfo);
    }

    return;
  }
}
