<?php

namespace HootDraft\Tests\Functional;

use HootDraft\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTest extends TestCase
{
  private Kernel $kernel;

  protected function setUp(): void
  {
    $this->kernel = new Kernel('test', false);
    $this->kernel->boot();
  }

  protected function tearDown(): void
  {
    $this->kernel->shutdown();
  }

  private function call(string $method, string $uri, ?string $json = null): Response
  {
    $server = $json === null ? [] : ['CONTENT_TYPE' => 'application/json'];

    return $this->kernel->handle(Request::create($uri, $method, [], [], [], $server, $json));
  }

  public function testApiRootReportsOk(): void
  {
    $response = $this->call('GET', '/');

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(['name' => 'HootDraft API', 'status' => 'ok'], json_decode($response->getContent(), true));
  }

  public function testUnknownRouteIsJson404(): void
  {
    $response = $this->call('GET', '/nope');

    $this->assertSame(404, $response->getStatusCode());
    $this->assertArrayHasKey('error', json_decode($response->getContent(), true));
  }
}
