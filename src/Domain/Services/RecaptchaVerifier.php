<?php

namespace HootDraft\Domain\Services;

use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RecaptchaVerifier {
  public function __construct(
    #[Autowire('%hootdraft.recaptcha_secret%')] private string $secret,
    #[Autowire('%kernel.debug%')] private bool $debug,
  ) {
  }

  /** @return string[] error codes, empty when the captcha response is good */
  public function Verify(?string $captchaResponse, ?string $clientIp): array {
    // Local development without a reCAPTCHA key (replaces the old "skip on 127.0.0.1" hack). In production
    // (debug off) a missing secret just fails every verification, it never opens the door.
    if ($this->secret === '' && $this->debug) {
      return [];
    }

    $result = (new ReCaptcha($this->secret))->verify((string) $captchaResponse, $clientIp);

    return $result->isSuccess() ? [] : ($result->getErrorCodes() ?: ['recaptcha-failed']);
  }
}
