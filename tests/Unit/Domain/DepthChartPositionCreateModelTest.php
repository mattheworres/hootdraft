<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use PHPUnit\Framework\TestCase;

class DepthChartPositionCreateModelTest extends TestCase
{
  /** @return DepthChartPosition[] */
  private function positions(array $positions): array
  {
    return array_map(function (array $values) {
      $position = new DepthChartPosition();
      [$position->position, $position->slots] = $values;
      return $position;
    }, $positions);
  }

  private function model(array $positions): DepthChartPositionCreateModel
  {
    $model = new DepthChartPositionCreateModel();
    $model->positions = $this->positions($positions);
    return $model;
  }

  public function testMatchesComparesNamesSlotsAndOrder(): void
  {
    $stored = $this->positions([['QB', 1], ['RB', 2]]);

    $this->assertTrue($this->model([['QB', 1], ['RB', 2]])->Matches($stored));
    $this->assertTrue($this->model([['QB', '1'], ['RB', '2']])->Matches($stored), 'stored slots can come back as strings');
    $this->assertFalse($this->model([['RB', 2], ['QB', 1]])->Matches($stored), 'order counts');
    $this->assertFalse($this->model([['QB', 1], ['RB', 3]])->Matches($stored));
    $this->assertFalse($this->model([['QB', 1]])->Matches($stored));
    $this->assertTrue($this->model([])->Matches([]));
  }
}
