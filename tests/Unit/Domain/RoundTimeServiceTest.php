<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\RoundTimeCreateModel;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Services\RoundTimeService;
use PHPUnit\Framework\TestCase;

class RoundTimeServiceTest extends TestCase
{
  /** A PDO that is "in a transaction" once beginTransaction was called and until it is committed or rolled back */
  private function pdo(int $expectedCommits, int $expectedRollBacks): \PDO
  {
    $inTransaction = false;
    $end = function () use (&$inTransaction) {
      $inTransaction = false;

      return true;
    };
    $pdo = $this->createMock(\PDO::class);
    $pdo->method('beginTransaction')->willReturnCallback(function () use (&$inTransaction) {
      $inTransaction = true;

      return true;
    });
    $pdo->method('inTransaction')->willReturnCallback(function () use (&$inTransaction) {
      return $inTransaction;
    });
    $pdo->expects($this->exactly($expectedCommits))->method('commit')->willReturnCallback($end);
    $pdo->expects($this->exactly($expectedRollBacks))->method('rollBack')->willReturnCallback($end);

    return $pdo;
  }

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

    $service = new RoundTimeService($repository, $this->createStub(PickRepository::class), $this->pdo(1, 0));

    $this->assertTrue($service->SaveRoundTimes($this->draft(), new RoundTimeCreateModel())->success);
  }

  public function testSaveRollsBackWhenAnInsertFails(): void
  {
    $repository = $this->createStub(RoundTimeRepository::class);
    $repository->method('Save')->willThrowException(new \Exception('Unable to save round times for 6'));

    $service = new RoundTimeService($repository, $this->createStub(PickRepository::class), $this->pdo(0, 1));
    $response = $service->SaveRoundTimes($this->draft(), new RoundTimeCreateModel());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to save round times for 6'], $response->errors);
  }
}
