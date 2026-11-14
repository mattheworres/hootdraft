<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Pick;
use PHPUnit\Framework\TestCase;

class PickTest extends TestCase
{
  public function testAPickIsSelectedOnceItHasAPickTimeAndADuration(): void
  {
    $cases = [
      'unselected' => [null, null, false],
      'time only' => ['2024-01-01 12:05:00', null, false],
      'duration only' => [null, 300, false],
      'selected' => ['2024-01-01 12:05:00', 300, true],
      // A zero duration counts: a Pick selected in the same second as the Pick before it
      'selected with a zero duration' => ['2024-01-01 12:05:00', 0, true],
    ];

    foreach ($cases as $name => [$pickTime, $pickDuration, $expected]) {
      $pick = new Pick();
      $pick->pick_time = $pickTime;
      $pick->pick_duration = $pickDuration;

      $this->assertSame($expected, $pick->HasBeenSelected(), $name);
    }
  }
}
