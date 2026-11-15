<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Services\TransactionService;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;

class TransactionServiceTest extends TestCase
{
  use FakesPdoTransactions;

  private function drafts(int $expectedFlushes): DraftRepository
  {
    $drafts = $this->createMock(DraftRepository::class);
    $drafts->expects($this->exactly($expectedFlushes))->method('FlushPendingCacheResets');

    return $drafts;
  }

  public function testCommitsAndReturnsTheResult(): void
  {
    $pdo = $this->pdo(1, 0);
    $service = new TransactionService($pdo, $this->drafts(1));

    $this->assertSame('saved', $service->Run(fn () => 'saved'));
    $this->assertFalse($pdo->inTransaction());
  }

  public function testRollsBackAndRethrowsAnException(): void
  {
    $pdo = $this->pdo(0, 1);
    $service = new TransactionService($pdo, $this->drafts(1));

    try {
      $service->Run(function () {
        throw new \Exception('Unable to save.');
      });
      $this->fail('The exception was not rethrown');
    } catch (\Exception $e) {
      $this->assertSame('Unable to save.', $e->getMessage());
    }

    $this->assertFalse($pdo->inTransaction());
  }

  public function testRollsBackAndRethrowsAnError(): void
  {
    $pdo = $this->pdo(0, 1);
    $service = new TransactionService($pdo, $this->drafts(1));

    $this->expectException(\TypeError::class);

    try {
      $service->Run(fn () => strlen(array()));
    } finally {
      $this->assertFalse($pdo->inTransaction());
    }
  }

  public function testRollsBackWhenTheCommitFails(): void
  {
    $pdo = $this->pdo(1, 1, false, new \PDOException('Deadlock found'));
    $service = new TransactionService($pdo, $this->drafts(1));

    $this->expectExceptionMessage('Deadlock found');

    $service->Run(fn () => null);
  }

  public function testJoinsAnOpenTransactionAndLeavesTheEndAndTheFlushToItsOwner(): void
  {
    $pdo = $this->pdo(0, 0, true);
    $service = new TransactionService($pdo, $this->drafts(0));

    $this->assertSame('joined', $service->Run(fn () => 'joined'));
    $this->assertTrue($pdo->inTransaction());
  }

  public function testAFailureInsideAJoinedTransactionIsRethrownWithoutRollingBack(): void
  {
    $pdo = $this->pdo(0, 0, true);
    $service = new TransactionService($pdo, $this->drafts(0));

    try {
      $service->Run(function () {
        throw new \Error('Broken step');
      });
      $this->fail('The error was not rethrown');
    } catch (\Error $e) {
      $this->assertSame('Broken step', $e->getMessage());
    }

    $this->assertTrue($pdo->inTransaction());
  }
}
