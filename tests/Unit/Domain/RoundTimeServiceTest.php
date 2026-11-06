<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\RoundTimeCreateModel;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Services\RoundTimeService;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;

class RoundTimeServiceTest extends TestCase
{
  use FakesPdoTransactions;

  private function draft(): Draft
  {
    $draft = new Draft();
    $draft->draft_id = 6;

    return $draft;
  }

  public function testSaveReplacesTheTimersInOneTransaction(): void
  {
    $repository = $this->createMock(RoundTimeRepository::class);
    $repository->expects($this->once())->method('DeleteAll')->with(6);
    $repository->expects($this->once())->method('Save')->willReturn(array());

    $service = new RoundTimeService($repository, $this->createStub(PickRepository::class), $this->transactions(1, 0));

    $this->assertTrue($service->SaveRoundTimes($this->draft(), new RoundTimeCreateModel())->success);
  }

  public function testSaveRollsBackWhenAnInsertFails(): void
  {
    $repository = $this->createStub(RoundTimeRepository::class);
    $repository->method('Save')->willThrowException(new \Exception('Unable to save round times for 6'));

    $service = new RoundTimeService($repository, $this->createStub(PickRepository::class), $this->transactions(0, 1));
    $response = $service->SaveRoundTimes($this->draft(), new RoundTimeCreateModel());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to save round times for 6'], $response->errors);
  }

  public function testAnErrorInASaveRollsBackAndIsRethrown(): void
  {
    $repository = $this->createStub(RoundTimeRepository::class);
    $repository->method('Save')->willThrowException(new \TypeError('Bad round time'));

    $service = new RoundTimeService($repository, $this->createStub(PickRepository::class), $this->transactions(0, 1));

    $this->expectException(\TypeError::class);

    $service->SaveRoundTimes($this->draft(), new RoundTimeCreateModel());
  }
}
