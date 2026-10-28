<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Services\DraftService;
use PHPUnit\Framework\TestCase;

class DraftServiceTest extends TestCase
{
  private DraftService $sut;
  private Draft $draft;

  protected function setUp(): void
  {
    $this->sut = $this->service($this->createStub(DraftRepository::class));
    $this->draft = new Draft();
  }

  private function service(
    DraftRepository $repository,
    ?DepthChartPositionRepository $depthChartPositions = null,
    ?PickRepository $picks = null,
    ?\PDO $pdo = null,
    ?TradeRepository $trades = null,
  ): DraftService {
    return new DraftService(
      new DraftDataRepository(),
      $repository,
      $depthChartPositions ?? $this->createStub(DepthChartPositionRepository::class),
      $this->createStub(ManagerRepository::class),
      $this->createStub(RoundTimeRepository::class),
      $picks ?? $this->createStub(PickRepository::class),
      $pdo ?? $this->createStub(\PDO::class),
      $trades ?? $this->createStub(TradeRepository::class),
      $this->createStub(DraftStatsRepository::class),
    );
  }

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

  private function draftWith(string $status, int $usingDepthCharts): Draft
  {
    $draft = new Draft();
    $draft->draft_id = 4;
    $draft->draft_status = $status;
    $draft->using_depth_charts = $usingDepthCharts;

    return $draft;
  }

  public function testDraftStatusDisplay(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertSame('Setting Up', $this->sut->GetDraftStatusDisplay($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertSame('In Progress', $this->sut->GetDraftStatusDisplay($this->draft));

    $this->draft->draft_status = 'complete';
    $this->assertSame('Completed', $this->sut->GetDraftStatusDisplay($this->draft));
  }

  public function testDraftSettingUp(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertTrue($this->sut->DraftSettingUp($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertFalse($this->sut->DraftSettingUp($this->draft));
  }

  public function testDraftInProgress(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertFalse($this->sut->DraftInProgress($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertTrue($this->sut->DraftInProgress($this->draft));
  }

  public function testDraftCompleted(): void
  {
    $this->draft->draft_status = 'complete';
    $this->assertTrue($this->sut->DraftComplete($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertFalse($this->sut->DraftComplete($this->draft));
  }

  public function testGetCurrentPickReadsTheLoadedDraft(): void
  {
    $draft = new Draft();
    $draft->draft_current_pick = '7';
    $repository = $this->createMock(DraftRepository::class);
    $repository->expects($this->once())->method('Load')->with(3)->willReturn($draft);

    $this->assertSame(7, $this->service($repository)->GetCurrentPick('3'));
  }

  public function testUpdateRewritesThePositionsWhileSettingUp(): void
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Update')->willReturnArgument(0);
    $positions = $this->createMock(DepthChartPositionRepository::class);
    $positions->expects($this->once())->method('DeleteAllDepthChartPositions')->with(4);
    $positions->expects($this->once())->method('Save');

    $response = $this->service($repository, $positions, null, $this->pdo(1, 0))
      ->UpdateDraft($this->draftWith('undrafted', 1), new DepthChartPositionCreateModel());

    $this->assertTrue($response->success);
  }

  public function testUpdateClearsThePositionsWhenDepthChartsAreTurnedOff(): void
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Update')->willReturnArgument(0);
    $positions = $this->createMock(DepthChartPositionRepository::class);
    $positions->expects($this->once())->method('DeleteAllDepthChartPositions')->with(4);
    $positions->expects($this->never())->method('Save');

    $response = $this->service($repository, $positions, null, $this->pdo(1, 0))->UpdateDraft($this->draftWith('undrafted', 0));

    $this->assertTrue($response->success);
  }

  public function testUpdateLeavesThePositionsAloneOnceTheDraftHasStarted(): void
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Update')->willReturnArgument(0);
    $positions = $this->createMock(DepthChartPositionRepository::class);
    $positions->expects($this->never())->method('DeleteAllDepthChartPositions');
    $positions->expects($this->never())->method('Save');

    $response = $this->service($repository, $positions, null, $this->pdo(1, 0))->UpdateDraft($this->draftWith('in_progress', 1));

    $this->assertTrue($response->success);
  }

  public function testUpdateRollsBackWhenSavingThePositionsFails(): void
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Update')->willReturnArgument(0);
    $positions = $this->createStub(DepthChartPositionRepository::class);
    $positions->method('Save')->willThrowException(new \Exception('Unable to create depth chart positions for 4'));

    $response = $this->service($repository, $positions, null, $this->pdo(0, 1))
      ->UpdateDraft($this->draftWith('undrafted', 1), new DepthChartPositionCreateModel());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to create depth chart positions for 4'], $response->errors);
  }

  public function testDeleteRemovesTheTradesAndPicksAndCommits(): void
  {
    $trades = $this->createMock(TradeRepository::class);
    $trades->expects($this->once())->method('DeleteAllTrades')->with(4);
    $picks = $this->createMock(PickRepository::class);
    $picks->expects($this->once())->method('DeleteAllPicks')->with(4);
    $repository = $this->createMock(DraftRepository::class);
    $repository->expects($this->once())->method('DeleteDraft')->with(4);

    $response = $this->service($repository, null, $picks, $this->pdo(1, 0), $trades)->DeleteDraft($this->draftWith('complete', 0));

    $this->assertTrue($response->success);
  }

  public function testStartingADraftClearsTradesAndPicksThenSetsUpPicks(): void
  {
    $trades = $this->createMock(TradeRepository::class);
    $trades->expects($this->once())->method('DeleteAllTrades')->with(4);
    $picks = $this->createMock(PickRepository::class);
    $picks->expects($this->once())->method('DeleteAllPicks')->with(4);
    $picks->expects($this->once())->method('SetupPicks');
    $draft = $this->draftWith('in_progress', 0);
    $repository = $this->createMock(DraftRepository::class);
    $repository->method('UpdateStatus')->willReturn($draft);
    $repository->expects($this->once())->method('SetDraftInProgress')->with($draft);

    $response = $this->service($repository, null, $picks, $this->pdo(1, 0), $trades)->UpdateDraftStatus($draft, 'undrafted');

    $this->assertTrue($response->success);
  }

  public function testOtherStatusChangesDoNotTouchTradesOrPicks(): void
  {
    $trades = $this->createMock(TradeRepository::class);
    $trades->expects($this->never())->method('DeleteAllTrades');
    $draft = $this->draftWith('undrafted', 0);
    $repository = $this->createMock(DraftRepository::class);
    $repository->method('UpdateStatus')->willReturn($draft);
    $repository->expects($this->never())->method('SetDraftInProgress');

    $response = $this->service($repository, null, null, $this->pdo(1, 0), $trades)->UpdateDraftStatus($draft, 'in_progress');

    $this->assertTrue($response->success);
  }

  public function testAFailedStartRollsBack(): void
  {
    $picks = $this->createStub(PickRepository::class);
    $picks->method('SetupPicks')->willThrowException(new \Exception('Unable to setup picks.'));
    $draft = $this->draftWith('in_progress', 0);
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('UpdateStatus')->willReturn($draft);

    $response = $this->service($repository, null, $picks, $this->pdo(0, 1))->UpdateDraftStatus($draft, 'undrafted');

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to setup picks.'], $response->errors);
  }

  public function testDeleteRollsBackWhenAStepFails(): void
  {
    $picks = $this->createStub(PickRepository::class);
    $picks->method('DeleteAllPicks')->willThrowException(new \Exception('Unable to delete picks for 4.'));
    $repository = $this->createMock(DraftRepository::class);
    $repository->expects($this->never())->method('DeleteDraft');

    $response = $this->service($repository, null, $picks, $this->pdo(0, 1))->DeleteDraft($this->draftWith('complete', 0));

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to delete picks for 4.'], $response->errors);
  }

  public function testDeleteJoinsAnOpenTransactionAndLeavesItToTheCaller(): void
  {
    $pdo = $this->createMock(\PDO::class);
    $pdo->method('inTransaction')->willReturn(true);
    $pdo->expects($this->never())->method('beginTransaction');
    $pdo->expects($this->never())->method('commit');
    $pdo->expects($this->never())->method('rollBack');
    $repository = $this->createMock(DraftRepository::class);
    $repository->expects($this->once())->method('DeleteDraft')->with(4);

    $this->assertTrue($this->service($repository, null, null, $pdo)->DeleteDraft($this->draftWith('complete', 0))->success);
  }

  public function testAFailedDeleteInsideAnOpenTransactionIsReportedWithoutRollingBack(): void
  {
    $pdo = $this->createMock(\PDO::class);
    $pdo->method('inTransaction')->willReturn(true);
    $pdo->expects($this->never())->method('rollBack');
    $picks = $this->createStub(PickRepository::class);
    $picks->method('DeleteAllPicks')->willThrowException(new \Exception('Unable to delete picks for 4.'));

    $response = $this->service($this->createStub(DraftRepository::class), null, $picks, $pdo)->DeleteDraft($this->draftWith('complete', 0));

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to delete picks for 4.'], $response->errors);
  }
}
