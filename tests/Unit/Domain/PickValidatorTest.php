<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\PickSearchModel;
use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Validators\PickValidator;
use PHPUnit\Framework\TestCase;

class PickValidatorTest extends TestCase
{
  private PickValidator $sut;
  private DepthChartPositionRepository $depthChartPositions;
  private Draft $draft;
  private Pick $pick;

  protected function setUp(): void
  {
    $this->depthChartPositions = $this->createStub(DepthChartPositionRepository::class);
    $this->sut = new PickValidator(new DraftDataRepository(), $this->depthChartPositions);

    $this->draft = new Draft();
    $this->draft->draft_id = 1;
    $this->draft->draft_sport = "NFL";
    $this->draft->draft_current_pick = 3;
    $this->draft->using_depth_charts = 1;

    $this->pick = new Pick();
    $this->pick->draft_id = 1;
    $this->pick->player_pick = 3;
    $this->pick->first_name = "Antonio";
    $this->pick->last_name = "Brown";
    $this->pick->team = "PIT";
    $this->pick->position = "WR";
    $this->pick->pick_time = "2024-09-01 12:00:00";
  }

  public function testAValidPickPassesAddAndUpdate(): void
  {
    $this->assertTrue($this->sut->IsPickValidForAdd($this->draft, $this->pick)->success);
    $this->assertTrue($this->sut->IsPickValidForUpdate($this->draft, $this->pick)->success);
  }

  public function testUpdateRequiresThePickToHaveBeenMade(): void
  {
    $this->pick->pick_time = null;

    $result = $this->sut->IsPickValidForUpdate($this->draft, $this->pick);

    $this->assertSame(["Pick #3 has not been made yet."], $result->errors);
  }

  public function testEnsuresFieldsArentEmptyOnAdd(): void
  {
    foreach (['first_name', 'last_name', 'team', 'position'] as $field) {
      $pick = clone $this->pick;
      $pick->$field = "";

      $result = $this->sut->IsPickValidForAdd($this->draft, $pick);

      $this->assertFalse($result->success, $field);
      $this->assertContains("One or more missing fields.", $result->errors, $field);
    }
  }

  public function testNullNamesAreMissingFieldsNotWarnings(): void
  {
    $this->pick->first_name = null;

    $result = $this->sut->IsPickValidForUpdate($this->draft, $this->pick);

    $this->assertSame(["One or more missing fields."], $result->errors);
  }

  public function testDraftIdMatchesOnAdd(): void
  {
    $this->pick->draft_id = 2;

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("Pick does not belong to draft #1.", $result->errors);
  }

  public function testNamesHaveAMaximumLength(): void
  {
    $this->pick->first_name = str_repeat("a", 256);
    $this->pick->last_name = str_repeat("b", 256);

    $result = $this->sut->IsPickValidForUpdate($this->draft, $this->pick);

    $this->assertSame(["First name is above maximum length.", "Last name is above maximum length."], $result->errors);
  }

  public function testPickMustBeTheCurrentPickOnAddOnly(): void
  {
    $this->pick->player_pick = 4;

    $this->assertSame(["Pick #4 is not the current pick for draft #1."], $this->sut->IsPickValidForAdd($this->draft, $this->pick)->errors);
    $this->assertTrue($this->sut->IsPickValidForUpdate($this->draft, $this->pick)->success);
  }

  public function testTeamAndPositionMustBeValidForTheSport(): void
  {
    $this->pick->team = "XXX";
    $this->pick->position = "QQ";

    $result = $this->sut->IsPickValidForUpdate($this->draft, $this->pick);

    $this->assertSame(["Team XXX is an invalid value.", "Position QQ is an invalid value."], $result->errors);
  }

  private function positions(int ...$ids): void
  {
    $this->depthChartPositions->method('LoadAll')->willReturn(array_map(function (int $id) {
      $position = new DepthChartPosition();
      $position->id = $id;

      return $position;
    }, $ids));
  }

  public function testDepthChartUpdateMustBeForAPickInTheDraft(): void
  {
    $this->pick->depth_chart_position_id = null;
    $this->assertTrue($this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick)->success);

    $this->pick->draft_id = 9;
    $result = $this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertSame(["Pick does not belong to draft #1."], $result->errors);
  }

  public function testDepthChartUpdateMustUseAPositionFromTheDraft(): void
  {
    $this->positions(7, 8);

    $this->pick->depth_chart_position_id = 8;
    $this->assertTrue($this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick)->success);

    $this->pick->depth_chart_position_id = 9999;
    $result = $this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertSame(["Depth chart position #9999 does not belong to draft #1."], $result->errors);
  }

  public function testDepthChartUpdateCanUnassignAPick(): void
  {
    $this->positions(7);
    $this->pick->depth_chart_position_id = null;

    $this->assertTrue($this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick)->success);
  }

  public function testDepthChartUpdateNeedsADraftUsingDepthCharts(): void
  {
    $this->positions(7);
    $this->draft->using_depth_charts = 0;
    $this->pick->depth_chart_position_id = 7;

    $result = $this->sut->IsPickValidForDepthChartUpdate($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertSame(["Draft #1 does not use depth charts."], $result->errors);
  }

  public function testSearchSortIsLimitedToADirection(): void
  {
    $this->assertSame('ASC', (new PickSearchModel(1, 'x', null, null, 'asc'))->sort);
    $this->assertSame('DESC', (new PickSearchModel(1, 'x', null, null, 'desc'))->sort);
    $this->assertSame('DESC', (new PickSearchModel(1, 'x', null, null, '1; DROP TABLE players'))->sort);
    $this->assertSame('DESC', (new PickSearchModel(1, 'x', null, null, null))->sort);
  }
}
