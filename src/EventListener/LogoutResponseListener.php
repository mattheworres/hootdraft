<?php

namespace HootDraft\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/** Logging out answers with JSON instead of the default redirect to "/". */
#[AsEventListener]
class LogoutResponseListener
{
  public function __invoke(LogoutEvent $event): void
  {
    $event->setResponse(new JsonResponse(['success' => true]));
  }
}
