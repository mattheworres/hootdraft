<?php

namespace HootDraft\Domain\Services;

use HootDraft\Domain\Models\MailMessage;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EmailService {
  public function __construct(
    #[Autowire('%hootdraft.mail.development%')] private bool $development,
    #[Autowire('%hootdraft.mail.server%')] private string $server,
    #[Autowire('%hootdraft.mail.port%')] private int $port,
    #[Autowire('%hootdraft.mail.user%')] private string $user,
    #[Autowire('%hootdraft.mail.pass%')] private string $pass,
    #[Autowire('%hootdraft.mail.use_encryption%')] private bool $useEncryption,
    #[Autowire('%hootdraft.mail.encryption%')] private string $encryption,
  ) {
  }

  public function SendMail(MailMessage $message) {
    $mailer = $this->createMailer();

    foreach ($message->to_addresses as $address => $name) {
      $mailer->addAddress($address, $name);
    }

    foreach ($message->cc_addresses as $address => $name) {
      $mailer->addCC($address, $name);
    }

    foreach ($message->bcc_addresses as $address => $name) {
      $mailer->addBCC($address, $name);
    }

    $mailer->isHTML($message->is_html);
    $mailer->Subject = $message->subject;
    $mailer->Body = $message->body;

    if (strlen((string) $message->altBody) > 0) {
      $mailer->AltBody = $message->altBody;
    }

    if (!$mailer->send()) {
      throw new \Exception("Unable to send mail: " . $mailer->ErrorInfo);
    }
  }

  private function createMailer(): PHPMailer {
    $mailer = new PHPMailer();

    //Uncomment this line to help debug issues with your SMTP server
    //$mailer->SMTPDebug = 2;

    $mailer->isSMTP();
    $mailer->Host = $this->server;
    $mailer->Port = $this->port;

    if ($this->development) {
      // A local mail catcher (MailHog etc.) speaks plain SMTP
      $mailer->SMTPAutoTLS = false;
    } else {
      $mailer->SMTPAuth = true;
      $mailer->Username = $this->user;
      $mailer->Password = $this->pass;

      if ($this->useEncryption) {
        $mailer->SMTPSecure = $this->encryption;
      }
    }

    $mailer->From = $this->user;
    $mailer->FromName = 'Hoot Draft';

    return $mailer;
  }
}
