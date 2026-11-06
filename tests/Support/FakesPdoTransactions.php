<?php

namespace HootDraft\Tests\Support;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Services\TransactionService;

/** For TestCases: fakes the PDO transaction calls that TransactionService makes. */
trait FakesPdoTransactions
{
  /** A PDO that is "in a transaction" once beginTransaction was called and until it is committed or rolled back */
  private function pdo(int $expectedCommits, int $expectedRollBacks, bool $alreadyInTransaction = false, ?\PDOException $commitFailure = null): \PDO
  {
    $inTransaction = $alreadyInTransaction;
    $end = function () use (&$inTransaction) {
      $inTransaction = false;

      return true;
    };
    $pdo = $this->createMock(\PDO::class);
    $pdo->expects($alreadyInTransaction ? $this->never() : $this->once())->method('beginTransaction')->willReturnCallback(function () use (&$inTransaction) {
      $inTransaction = true;

      return true;
    });
    $pdo->method('inTransaction')->willReturnCallback(function () use (&$inTransaction) {
      return $inTransaction;
    });
    $commit = $pdo->expects($this->exactly($expectedCommits))->method('commit');
    $commitFailure === null ? $commit->willReturnCallback($end) : $commit->willThrowException($commitFailure);
    $pdo->expects($this->exactly($expectedRollBacks))->method('rollBack')->willReturnCallback($end);

    return $pdo;
  }

  /** A TransactionService on the fake PDO, for services whose tests do not check the Draft cache flush */
  private function transactions(int $expectedCommits, int $expectedRollBacks): TransactionService
  {
    return new TransactionService($this->pdo($expectedCommits, $expectedRollBacks), $this->createStub(DraftRepository::class));
  }
}
