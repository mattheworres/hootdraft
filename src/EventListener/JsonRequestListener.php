<?php

namespace HootDraft\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** If we get application/json, decode the data into the request bag so controllers can use it. */
#[AsEventListener(event: 'kernel.request', priority: 100)]
class JsonRequestListener
{
  public function __invoke(RequestEvent $event): void
  {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();

    if (str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
      $content = $request->getContent();

      if ($content === '') {
        return;
      }

      try {
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
      } catch (\JsonException) {
        throw new BadRequestHttpException('Request body is not valid JSON.');
      }

      if (!is_array($data)) {
        throw new BadRequestHttpException('Request body must be a JSON object.');
      }

      $request->request->replace($data);
    }
  }
}
