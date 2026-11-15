<?php

namespace HootDraft\Tests\Unit\EventListener;

use HootDraft\EventListener\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiExceptionListenerTest extends TestCase
{
  private function handle(\Throwable $e, bool $debug): ExceptionEvent
  {
    $event = new ExceptionEvent($this->createStub(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST, $e);
    (new ApiExceptionListener(new NullLogger(), $debug))($event);

    return $event;
  }

  public function testHttpExceptionsKeepTheirStatus(): void
  {
    $response = $this->handle(new NotFoundHttpException('Gone'), false)->getResponse();

    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame(['error' => 'Gone'], json_decode($response->getContent(), true));
  }

  public function testUnexpectedErrorsHideTheirMessageOutsideDebug(): void
  {
    $response = $this->handle(new \RuntimeException('secret SQL detail'), false)->getResponse();

    $this->assertSame(500, $response->getStatusCode());
    $this->assertStringNotContainsString('secret', $response->getContent());
  }

  public function testUnexpectedErrorsShowTheirMessageInDebug(): void
  {
    $response = $this->handle(new \RuntimeException('secret SQL detail'), true)->getResponse();

    $this->assertSame(['error' => 'secret SQL detail'], json_decode($response->getContent(), true));
  }
}
