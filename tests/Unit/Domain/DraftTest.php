<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use PHPUnit\Framework\TestCase;

class DraftTest extends TestCase
{
  public function testUsesDepthChartsReadsTheStoredAndTheLoadedForm(): void
  {
    foreach ([[1, true], ['1', true], [true, true], [0, false], ['0', false], [false, false], [null, false]] as [$stored, $expected]) {
      $draft = new Draft();
      $draft->using_depth_charts = $stored;

      $this->assertSame($expected, $draft->UsesDepthCharts(), var_export($stored, true));
    }
  }
}
