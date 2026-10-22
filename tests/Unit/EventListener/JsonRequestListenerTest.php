<?php

namespace HootDraft\Tests\Unit\EventListener;

use HootDraft\EventListener\JsonRequestListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class JsonRequestListenerTest extends TestCase
{
  private function dispatch(Request $request): Request
  {
    (new JsonRequestListener())(new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

    return $request;
  }

  public function testJsonBodyIsDecodedIntoRequestBag(): void
  {
    $request = $this->dispatch(Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json; charset=utf-8'], '{"a":1,"b":"two"}'));

    $this->assertSame(['a' => 1, 'b' => 'two'], $request->request->all());
  }

  public function testInvalidJsonIsABadRequest(): void
  {
    $this->expectException(BadRequestHttpException::class);

    $this->dispatch(Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{nope'));
  }

  public function testNonObjectJsonIsABadRequest(): void
  {
    $this->expectException(BadRequestHttpException::class);

    $this->dispatch(Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '"abc"'));
  }

  public function testEmptyJsonBodyIsLeftEmpty(): void
  {
    $request = $this->dispatch(Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], ''));

    $this->assertSame([], $request->request->all());
  }

  public function testOtherContentTypesAreUntouched(): void
  {
    $request = $this->dispatch(Request::create('/', 'POST', ['form' => 'x']));

    $this->assertSame(['form' => 'x'], $request->request->all());
  }
}
