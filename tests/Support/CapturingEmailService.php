<?php

namespace HootDraft\Tests\Support;

use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Services\EmailService;

/** Test-env replacement for EmailService: keeps the messages instead of talking SMTP. */
class CapturingEmailService extends EmailService
{
  /** @var MailMessage[] */
  public array $sent = [];

  public function SendMail(MailMessage $message)
  {
    $this->sent[] = $message;
  }
}
