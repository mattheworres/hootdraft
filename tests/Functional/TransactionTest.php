<?php

namespace HootDraft\Tests\Functional;

use HootDraft\Domain\Services\TransactionService;
use PHPUnit\Framework\Attributes\Group;

/** The transaction helper against the real database connection */
#[Group('database')]
class TransactionTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'transaction-test.example';
  }

  public function testAnErrorRollsBackAndLeavesTheConnectionOutsideATransaction(): void
  {
    $transactions = static::getContainer()->get(TransactionService::class);

    try {
      $transactions->Run(function () {
        $this->createUser('Rolled');

        // A PHP \Error, not an \Exception: the services catch only \Exception, so the helper must roll this back itself
        return strlen(array());
      });
      $this->fail('The error was not rethrown');
    } catch (\TypeError) {
    }

    $this->assertFalse(static::getContainer()->get(\PDO::class)->inTransaction());
    $this->assertSame(0, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM users WHERE email = 'rolled@transaction-test.example'"));
  }

  public function testACommittedRunKeepsItsWrites(): void
  {
    $transactions = static::getContainer()->get(TransactionService::class);

    $transactions->Run(fn () => $this->createUser('Kept'));

    $this->assertFalse(static::getContainer()->get(\PDO::class)->inTransaction());
    $this->assertSame(1, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM users WHERE email = 'kept@transaction-test.example'"));
  }
}
