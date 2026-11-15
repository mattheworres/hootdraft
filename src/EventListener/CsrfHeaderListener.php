<?php

namespace HootDraft\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defense in depth for cookie authentication, on top of the SameSite=Lax session cookie: every request that
 * can change state must carry a custom header. Browsers only let same-origin scripts set one (anything else
 * needs a CORS preflight, which this API never approves), so a forged cross-site form post can't.
 */
#[AsEventListener(event: 'kernel.request', priority: 20)] // after routing, before the firewall
class CsrfHeaderListener
{
  public const HEADER = 'X-Requested-With';
  public const VALUE = 'XMLHttpRequest';

  public function __invoke(RequestEvent $event): void
  {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();

    if ($request->isMethodSafe() || $request->headers->get(self::HEADER) === self::VALUE) {
      return;
    }

    throw new AccessDeniedHttpException(sprintf('Missing %s header.', self::HEADER));
  }
}
