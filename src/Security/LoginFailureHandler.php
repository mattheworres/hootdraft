<?php

namespace HootDraft\Security;

use HootDraft\Domain\Models\EmailVerificationResend;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\LoginUserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Always the same message, whether the email is unknown, the password is wrong or the account is Disabled. The one
 * exception is an Email Unverified user who gave the right password: they are told what to do (the UserChecker only
 * throws after the password check, so this reveals nothing to somebody who does not know the password).
 */
class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
  public function __construct(
    private LoginUserRepository $repository,
    private LoginUserService $service,
  ) {
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
  {
    if ($exception instanceof EmailUnverifiedException) {
      $response = new HootDraftResponse(false, [$this->emailUnverifiedMessage($exception->getEmail())]);
    } else {
      $response = new HootDraftResponse(false, ['Invalid credentials.']);
    }

    return new JsonResponse($response, $response->responseType());
  }

  /** Also sends a new Email Verification Key link when the old one has expired. */
  private function emailUnverifiedMessage(string $email): string
  {
    return match ($this->service->ResendEmailVerificationIfExpired($this->repository->Load($email))) {
      EmailVerificationResend::Sent => 'Your account email is still unverified. Your link expired, so we sent a new one. Please click the link in your email to continue.',
      EmailVerificationResend::Failed => 'Your account email is still unverified. Your link expired and we could not send a new one. Please try again later.',
      EmailVerificationResend::NotNeeded => 'Your account email is still unverified. Please click the link in your email to continue.',
    };
  }
}
