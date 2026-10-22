<?php

namespace HootDraft\EventListener;

use HootDraft\Http\Check\RouteCheck;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Runs the checks named in a route's `_checks` default (in order) before the controller. The first
 * check to return a Response wins, as with chained ->before() handlers in Silex.
 *
 *   #[Route('/draft/{draft_id}/picks', defaults: ['_checks' => ['draftViewable', 'draftInProgressOrCompleted']])]
 */
#[AsEventListener(event: 'kernel.controller')]
class RouteCheckListener
{
  /** @var array<string, RouteCheck> */
  private array $checks = [];

  /** @param iterable<RouteCheck> $checks */
  public function __construct(#[AutowireIterator('hootdraft.route_check')] iterable $checks)
  {
    foreach ($checks as $check) {
      $this->checks[$check::alias()] = $check;
    }
  }

  public function __invoke(ControllerEvent $event): void
  {
    $request = $event->getRequest();

    foreach ((array) $request->attributes->get('_checks', []) as $alias) {
      if (!isset($this->checks[$alias])) {
        throw new \LogicException(sprintf('Route "%s" requires unknown check "%s".', $request->attributes->get('_route'), $alias));
      }

      $response = $this->checks[$alias]->check($request);

      if ($response !== null) {
        $event->setController(static fn() => $response);
        return;
      }
    }
  }
}
