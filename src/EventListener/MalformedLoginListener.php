<?php

namespace HootDraft\EventListener;

use HootDraft\Security\LoginFailureHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * The one place a /login request that can't hold credentials fails like a wrong password. JsonRequestListener or
 * json_login throws a 400 for a body that is not valid JSON, is missing _email / _password, or has a non-string one.
 * AuthenticationController::login throws one for a non-JSON content type. The login form only reads `errors`, so
 * these get the login failure response instead of ApiExceptionListener's {"error": "..."}.
 */
#[AsEventListener(event: 'kernel.exception', priority: 10)] // before ApiExceptionListener, which answers every exception
class MalformedLoginListener
{
  public function __construct(
    private LoginFailureHandler $failureHandler,
    #[Autowire(service: 'security.http_utils')] private HttpUtils $httpUtils,
  ) {
  }

  public function __invoke(ExceptionEvent $event): void
  {
    $request = $event->getRequest();

    if (!$event->getThrowable() instanceof BadRequestHttpException || !$this->httpUtils->checkRequestPath($request, 'login')) {
      return;
    }

    $event->setResponse($this->failureHandler->onAuthenticationFailure($request, new BadCredentialsException()));
  }
}
