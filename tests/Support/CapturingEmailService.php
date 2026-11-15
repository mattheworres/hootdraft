<?php

namespace HootDraft\Tests\Support;

use HootDraft\Domain\Models\MailMessage;
use HootDraft\Domain\Services\EmailService;

/** Test-env replacement for EmailService: keeps the messages instead of talking SMTP. */
class CapturingEmailService extends EmailService
{
  /** @var MailMessage[] */
  public array $sent = [];

  /** Set to make the next SendMail calls fail, as a down SMTP server would. */
  public bool $failing = false;

  public function SendMail(MailMessage $message)
  {
    if ($this->failing) {
      throw new \RuntimeException('SMTP is down.');
    }

    $this->sent[] = $message;
  }
}
