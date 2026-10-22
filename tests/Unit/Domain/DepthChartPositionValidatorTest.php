<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Validators\DepthChartPositionValidator;
use PHPUnit\Framework\TestCase;

class DepthChartPositionValidatorTest extends TestCase
{
  private DepthChartPositionValidator $sut;

  protected function setUp(): void
  {
    $this->sut = new DepthChartPositionValidator(new DraftDataRepository());
  }

  private function model(array $slotsByPosition): DepthChartPositionCreateModel
  {
    $model = new DepthChartPositionCreateModel();

    foreach ($slotsByPosition as $name => $slots) {
      $position = new DepthChartPosition();
      $position->position = (string) $name;
      $position->slots = $slots;
      $model->positions[] = $position;
    }

    return $model;
  }

  public function testSportMustBeAKnownOne(): void
  {
    $this->assertTrue($this->sut->IsDraftSportValid('NFL')->success);
    $this->assertSame(['Draft sport is an invalid value.'], $this->sut->IsDraftSportValid('XYZ')->errors);
    $this->assertFalse($this->sut->IsDraftSportValid('')->success);
    $this->assertFalse($this->sut->IsDraftSportValid(null)->success);
    $this->assertFalse($this->sut->IsDraftSportValid('FOOTBALL')->success);
  }

  public function testPositionsNeedSlotsUniqueNamesAndStayUnderTheLimits(): void
  {
    $this->assertTrue($this->sut->AreDepthChartPositionsValid($this->model(['QB' => 1, 'RB' => 2]))->success);

    $this->assertSame(['One or more of the positions are not unique.'], $this->sut->AreDepthChartPositionsValid($this->model(['QB' => 1, 'qb' => 1]))->errors);
    $this->assertSame(['The depth chart positions must specify at least 1 slot'], $this->sut->AreDepthChartPositionsValid($this->model(['QB' => 0]))->errors);
    $this->assertSame(['The depth chart positions cannot specify more than 30 slots in total.'], $this->sut->AreDepthChartPositionsValid($this->model(['QB' => 31]))->errors);
  }

  public function testEachPositionNeedsAWholeNonNegativeNumberOfSlots(): void
  {
    // The total (1) is fine, but one position has -5 slots
    $negative = $this->sut->AreDepthChartPositionsValid($this->model(['QB' => -5, 'RB' => 6]));
    $this->assertFalse($negative->success);
    $this->assertSame(['Depth chart position #1 must have a whole number of slots, zero or more.'], $negative->errors);

    $this->assertFalse($this->sut->AreDepthChartPositionsValid($this->model(['QB' => 'abc', 'RB' => 2]))->success);
    $this->assertFalse($this->sut->AreDepthChartPositionsValid($this->model(['QB' => 1.5, 'RB' => 2]))->success);
    $this->assertFalse($this->sut->AreDepthChartPositionsValid($this->model(['QB' => null, 'RB' => 2]))->success);

    // Numeric strings are what a JSON or form body can carry
    $this->assertTrue($this->sut->AreDepthChartPositionsValid($this->model(['QB' => '1', 'RB' => '2']))->success);
  }

  public function testEachPositionNeedsAName(): void
  {
    $model = $this->model(['QB' => 1]);
    $unnamed = new DepthChartPosition();
    $unnamed->slots = 1;
    $model->positions[] = $unnamed;

    $response = $this->sut->AreDepthChartPositionsValid($model);

    $this->assertFalse($response->success);
    $this->assertSame(['Depth chart position #2 is missing its position.'], $response->errors);
  }
}
