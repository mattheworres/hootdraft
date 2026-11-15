<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Services\ClientTimeFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClientTimeFormatterTest extends TestCase
{
  public static function times(): array
  {
    return [
      'database time gets UTC appended' => ['2026-11-14 10:00:00', '2026-11-14 10:00:00 UTC'],
      'already marked UTC is unchanged' => ['2026-11-14 10:00:00 UTC', '2026-11-14 10:00:00 UTC'],
      'null stays null' => [null, null],
      'empty string becomes null' => ['', null],
    ];
  }

  #[DataProvider('times')]
  public function testFormat(?string $time, ?string $expected): void
  {
    $this->assertSame($expected, (new ClientTimeFormatter())->MarkAsUtc($time));
  }
}
