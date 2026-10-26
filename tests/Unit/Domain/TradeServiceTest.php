<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Services\TradeService;
use PHPUnit\Framework\TestCase;

class TradeServiceTest extends TestCase
{
  private function trade(): Trade
  {
    $newManager = new Manager();
    $newManager->manager_id = 9;

    $asset = new TradeAsset();
    $asset->player = new Pick();
    $asset->player->player_id = 3;
    $asset->newmanager = $newManager;

    $trade = new Trade();
    $trade->trade_assets[] = $asset;

    return $trade;
  }

  private function draft(): Draft
  {
    $draft = new Draft();
    $draft->draft_counter = 4;

    return $draft;
  }

  public function testEnterTradeMovesThePicksAndCommits(): void
  {
    $picks = $this->createMock(PickRepository::class);
    $picks->expects($this->once())->method('UpdatePick')->with($this->callback(
      fn(Pick $pick) => $pick->manager_id === 9 && $pick->player_counter === 5
    ));
    $trades = $this->createStub(TradeRepository::class);
    $trades->method('SaveTrade')->willReturnArgument(0);
    $trades->method('SaveAssets')->willReturnArgument(0);
    $drafts = $this->createStub(DraftRepository::class);
    $drafts->method('IncrementDraftCounter')->willReturn(5);
    $pdo = $this->createMock(\PDO::class);
    $pdo->expects($this->once())->method('beginTransaction');
    $pdo->expects($this->once())->method('commit');
    $pdo->expects($this->never())->method('rollBack');

    $draft = $this->draft();
    $response = (new TradeService($picks, $trades, $drafts, $pdo))->EnterTrade($draft, $this->trade());

    $this->assertTrue($response->success);
    $this->assertSame(5, $draft->draft_counter);
  }

  public function testAFailureRollsEverythingBack(): void
  {
    $picks = $this->createStub(PickRepository::class);
    $trades = $this->createStub(TradeRepository::class);
    $trades->method('SaveTrade')->willThrowException(new \Exception('Unable to save trade.'));
    $drafts = $this->createMock(DraftRepository::class);
    $drafts->expects($this->never())->method('IncrementDraftCounter');
    $pdo = $this->createMock(\PDO::class);
    $pdo->method('inTransaction')->willReturn(true);
    $pdo->expects($this->once())->method('rollBack');
    $pdo->expects($this->never())->method('commit');

    $response = (new TradeService($picks, $trades, $drafts, $pdo))->EnterTrade($this->draft(), $this->trade());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to save trade.'], $response->errors);
    $this->assertSame(400, $response->responseType());
  }
}
