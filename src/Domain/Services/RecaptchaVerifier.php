<?php

namespace HootDraft\Domain\Services;

use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RecaptchaVerifier {
  /** Our own error code, for a failure the reCAPTCHA library gave no code for */
  private const FAILED = 'recaptcha-failed';

  public function __construct(
    #[Autowire('%hootdraft.recaptcha_secret%')] private string $secret,
    #[Autowire('%kernel.debug%')] private bool $debug,
    private LoggerInterface $logger,
  ) {
  }

  /** @return string[] error codes, empty when the captcha response is good */
  public function Verify(?string $captchaResponse, ?string $clientIp): array {
    if ($this->secret === '') {
      // Local development without a reCAPTCHA key (replaces the old "skip on 127.0.0.1" hack)
      if ($this->debug) {
        return [];
      }

      // In production a missing secret fails every verification, it never opens the door
      $this->logger->critical('RECAPTCHA_SECRET is empty, so every reCAPTCHA check fails. Set it in .env.local or the environment.');

      return [self::FAILED];
    }

    $result = (new ReCaptcha($this->secret))->verify((string) $captchaResponse, $clientIp);

    return $result->isSuccess() ? [] : ($result->getErrorCodes() ?: [self::FAILED]);
  }
}
