<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Services\SaltService;
use PHPUnit\Framework\TestCase;

class SaltServiceTest extends TestCase
{
  public function testSaltGeneratedIsSixteenCharactersLong(): void
  {
    $this->assertSame(16, strlen((new SaltService())->GenerateSalt()));
  }

  public function testSaltForUrlGeneratedDoesntContainSlashes(): void
  {
    $sut = new SaltService();

    for ($i = 0; $i < 30; $i++) {
      $this->assertStringNotContainsString('/', $sut->GenerateSaltForUrl());
    }
  }

  public function testUrlDecodeRestoresPlusSignsTurnedIntoSpaces(): void
  {
    $this->assertSame('ab+cd', (new SaltService())->UrlDecodeSalt('ab cd'));
  }
}
