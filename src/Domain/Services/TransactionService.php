<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Repositories\DraftRepository;

/** The one place that begins, commits and rolls back database transactions for the services. */
class TransactionService {
  public function __construct(private \PDO $pdo, private DraftRepository $drafts) {
  }

  /**
   * Runs $work in a transaction and returns what it returns. Any \Throwable, PHP \Errors included, rolls back and is
   * rethrown. Pending Draft cache resets are flushed after the commit or the rollback.
   *
   * When a transaction is already open, $work joins it (deleting a user deletes all their drafts as one unit): a
   * failure is rethrown as is, and the caller that opened the transaction owns the commit, the rollback and the flush.
   */
  public function Run(callable $work): mixed {
    if ($this->pdo->inTransaction()) {
      return $work();
    }

    $this->pdo->beginTransaction();

    try {
      $result = $work();
      $this->pdo->commit();

      return $result;
    } catch (\Throwable $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $e;
    } finally {
      $this->drafts->FlushPendingCacheResets();
    }
  }
}
