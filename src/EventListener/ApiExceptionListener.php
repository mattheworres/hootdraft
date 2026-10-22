<?php

namespace HootDraft\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Every error comes back as {"error": "..."} like the old Silex handler did. Unlike it, HTTP exceptions
 * keep their own status (404, 403, ...) and unexpected errors only reveal their message in debug.
 */
#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
  public function __construct(
    private LoggerInterface $logger,
    #[Autowire('%kernel.debug%')] private bool $debug,
  ) {
  }

  public function __invoke(ExceptionEvent $event): void
  {
    $e = $event->getThrowable();

    if ($e instanceof HttpExceptionInterface) {
      $event->setResponse(new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode(), $e->getHeaders()));
      return;
    }

    $this->logger->error($e->getMessage(), ['exception' => $e]);

    $message = $this->debug ? $e->getMessage() : 'An unexpected error occurred.';
    $event->setResponse(new JsonResponse(['error' => $message], Response::HTTP_INTERNAL_SERVER_ERROR));
  }
}
