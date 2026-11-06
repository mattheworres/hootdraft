<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\PickSearchRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Domain\Services\PickService;
use HootDraft\Domain\Services\RoundTimeService;
use HootDraft\Domain\Services\TransactionService;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PickServiceTest extends TestCase
{
  use FakesPdoTransactions;

  private function service(TransactionService $transactions, PickRepository $picks, DraftRepository $drafts): PickService
  {
    return new PickService(
      $picks,
      $drafts,
      $this->createStub(DraftDataRepository::class),
      $this->createStub(RoundTimeService::class),
      $this->createStub(PickSearchRepository::class),
      $this->createStub(DraftService::class),
      $transactions,
      $this->createStub(DraftStatsRepository::class),
      new NullLogger(),
    );
  }

  private function draft(): Draft
  {
    $draft = new Draft();
    $draft->draft_id = 6;
    $draft->draft_counter = 10;
    $draft->draft_current_pick = 4;
    $draft->draft_start_time = '2026-01-01 00:00:00';

    return $draft;
  }

  private function pick(): Pick
  {
    $pick = new Pick();
    $pick->player_pick = 4;

    return $pick;
  }

  public function testAddingTheCurrentPickCommits(): void
  {
    $picks = $this->createMock(PickRepository::class);
    $picks->expects($this->once())->method('AddPick')->willReturnArgument(0);
    $drafts = $this->createStub(DraftRepository::class);
    $drafts->method('LockCurrentPick')->willReturn(4);
    $drafts->method('IncrementDraftCounter')->willReturn(11);
    $drafts->method('MoveDraftForward')->willReturnArgument(0);

    $response = $this->service($this->transactions(1, 0), $picks, $drafts)->AddPick($this->draft(), $this->pick());

    $this->assertTrue($response->success);
    $this->assertSame(11, $response->pick->player_counter);
  }

  public function testADoubleSubmitIsRefusedUnderTheLockAndRolledBack(): void
  {
    $picks = $this->createMock(PickRepository::class);
    $picks->expects($this->never())->method('AddPick');
    $drafts = $this->createMock(DraftRepository::class);
    $drafts->expects($this->once())->method('LockCurrentPick')->willReturn(5);
    $drafts->expects($this->never())->method('IncrementDraftCounter');

    $response = $this->service($this->transactions(0, 1), $picks, $drafts)->AddPick($this->draft(), $this->pick());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to add pick: Pick #4 is no longer the current pick.'], $response->errors);
  }

  public function testAnErrorRollsBackAndIsRethrown(): void
  {
    $picks = $this->createStub(PickRepository::class);
    $picks->method('AddPick')->willThrowException(new \TypeError('Bad pick'));
    $drafts = $this->createStub(DraftRepository::class);
    $drafts->method('LockCurrentPick')->willReturn(4);

    $service = $this->service($this->transactions(0, 1), $picks, $drafts);

    $this->expectException(\TypeError::class);

    $service->AddPick($this->draft(), $this->pick());
  }
}
