<?php

namespace HootDraft\Tests\Unit\EventListener;

use HootDraft\EventListener\RouteCheckListener;
use HootDraft\Http\Check\RouteCheck;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouteCheckListenerTest extends TestCase
{
  private function event(array $checks): ControllerEvent
  {
    $request = new Request();
    $request->attributes->set('_route', 'test');
    $request->attributes->set('_checks', $checks);

    return new ControllerEvent($this->createStub(HttpKernelInterface::class), static fn() => new Response('controller'), $request, HttpKernelInterface::MAIN_REQUEST);
  }

  private function check(string $alias, ?Response $result): RouteCheck
  {
    return new class($alias, $result) implements RouteCheck {
      public static string $alias;
      public int $calls = 0;

      public function __construct(string $alias, private ?Response $result)
      {
        // alias() is static, so tests build at most one fake check at a time
        self::$alias = $alias;
      }

      public static function alias(): string
      {
        return self::$alias;
      }

      public function check(Request $request): ?Response
      {
        $this->calls++;
        return $this->result;
      }
    };
  }

  public function testPassingChecksLeaveControllerAlone(): void
  {
    $sut = new RouteCheckListener([$this->check('ok', null)]);
    $event = $this->event(['ok']);

    $sut($event);

    $this->assertSame('controller', ($event->getController())()->getContent());
  }

  public function testFirstFailingCheckReplacesControllerAndStopsTheChain(): void
  {
    $deny = new JsonResponse(['error' => 'nope'], 403);
    $sut = new RouteCheckListener([$this->check('deny', $deny)]);
    $event = $this->event(['deny']);

    $sut($event);

    $this->assertSame($deny, ($event->getController())());
  }

  public function testUnknownCheckIsALogicError(): void
  {
    $this->expectException(\LogicException::class);

    (new RouteCheckListener([]))($this->event(['missing']));
  }

  public function testRoutesWithoutChecksAreUntouched(): void
  {
    $event = $this->event([]);

    (new RouteCheckListener([]))($event);

    $this->assertSame('controller', ($event->getController())()->getContent());
  }
}
