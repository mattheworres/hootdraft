<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Models\TradeBuildResult;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Services\ClientTimeFormatter;
use HootDraft\Domain\Services\TradeService;
use HootDraft\Domain\Services\TransactionService;
use HootDraft\Domain\Validators\TradeValidator;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;

class TradeServiceTest extends TestCase
{
  use FakesPdoTransactions;

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
    $draft->draft_id = 1;
    $draft->draft_counter = 4;
    $draft->draft_rounds = 10;
    $draft->draft_current_round = 2;

    return $draft;
  }

  private function service(
    ?PickRepository $picks = null,
    ?TradeRepository $trades = null,
    ?DraftRepository $drafts = null,
    ?ManagerRepository $managers = null,
    ?TransactionService $transactions = null,
  ): TradeService {
    return new TradeService(
      $picks ?? $this->createStub(PickRepository::class),
      $trades ?? $this->createStub(TradeRepository::class),
      $drafts ?? $this->createStub(DraftRepository::class),
      $managers ?? $this->createStub(ManagerRepository::class),
      new TradeValidator(),
      $transactions ?? $this->createStub(TransactionService::class),
      new ClientTimeFormatter(),
    );
  }

  public function testGetTradesMarksTradeTimesAsUtcForTheClient(): void
  {
    $trade = new Trade();
    $trade->trade_time = '2026-11-14 10:00:00';
    $trades = $this->createMock(TradeRepository::class);
    $trades->expects($this->once())->method('GetTrades')->with(1)->willReturn([$trade]);

    $result = $this->service(trades: $trades)->GetTrades(1);

    $this->assertSame('2026-11-14 10:00:00 UTC', $result[0]->trade_time);
  }

  /** Managers 7 and 8 of draft 1. Picks 3 and 4 belong to manager 7, pick 5 to manager 8, and pick 6 to manager 99. Only pick 3 has been selected. */
  private function buildingService(): TradeService
  {
    $managers = $this->createStub(ManagerRepository::class);
    $managers->method('Load')->willReturnCallback(function ($id) {
      $manager = new Manager();
      $manager->manager_id = (int) $id;
      $manager->draft_id = 1;

      return $manager;
    });

    $owners = [3 => 7, 4 => 7, 5 => 8, 6 => 99];
    $picks = $this->createStub(PickRepository::class);
    $picks->method('Load')->willReturnCallback(function ($id) use ($owners) {
      $pick = new Pick();
      $pick->player_id = (int) $id;
      $pick->draft_id = 1;
      $pick->manager_id = $owners[$id];

      if ($pick->player_id === 3) {
        $pick->pick_time = '2024-01-01 12:05:00';
        $pick->pick_duration = 300;
      }

      return $pick;
    });

    return $this->service(picks: $picks, managers: $managers);
  }

  public function testBuildTradeMovesEachAssetToTheOtherManager(): void
  {
    $built = $this->buildingService()->BuildTrade($this->draft(), '7', '8', ['3', '5']);

    $this->assertInstanceOf(TradeBuildResult::class, $built);
    $this->assertSame([], $built->errors);
    $trade = $built->trade;
    $this->assertSame(1, $trade->draft_id);
    $this->assertSame(2, $trade->trade_round);
    $this->assertSame(7, $trade->manager1->manager_id);
    $this->assertSame(8, $trade->manager2->manager_id);

    $this->assertSame(
      [[3, 7, 8, 7, 8, 1], [5, 8, 7, 8, 7, 0]],
      array_map(fn(TradeAsset $asset) => [
        $asset->player->player_id,
        (int) $asset->oldmanager_id,
        (int) $asset->newmanager_id,
        $asset->oldmanager->manager_id,
        $asset->newmanager->manager_id,
        $asset->was_drafted,
      ], $trade->trade_assets),
    );
  }

  public function testBuildTradeRefusesAMissingAssetList(): void
  {
    $built = $this->buildingService()->BuildTrade($this->draft(), '7', '8', null);

    $this->assertNull($built->trade);
    $this->assertSame(['Unable to build trade: Invalid trade data.'], $built->errors);
  }

  public function testBuildTradeRefusesAnAssetOfNeitherManager(): void
  {
    $built = $this->buildingService()->BuildTrade($this->draft(), '7', '8', ['3', '6']);

    $this->assertNull($built->trade);
    $this->assertSame(['Unable to build trade: Invalid trade data.'], $built->errors);
  }

  public function testBuildTradeRefusesAManagerThatCannotBeLoaded(): void
  {
    $managers = $this->createStub(ManagerRepository::class);
    $managers->method('Load')->willThrowException(new \Exception('Unable to load manager #8.'));

    $built = $this->service(managers: $managers)->BuildTrade($this->draft(), '7', '8', ['3']);

    $this->assertNull($built->trade);
    $this->assertSame(['Unable to build trade: Unable to load manager #8.'], $built->errors);
  }

  public function testBuildTradeReturnsTheValidationErrors(): void
  {
    $built = $this->buildingService()->BuildTrade($this->draft(), '7', '8', ['3', '3']);

    $this->assertNull($built->trade);
    $this->assertSame(['One or more of the trade assets are duplicate.'], $built->errors);
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

    $draft = $this->draft();
    $response = $this->service($picks, $trades, $drafts, transactions: $this->transactions(1, 0))->EnterTrade($draft, $this->trade());

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

    $response = $this->service($picks, $trades, $drafts, transactions: $this->transactions(0, 1))->EnterTrade($this->draft(), $this->trade());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to save trade.'], $response->errors);
    $this->assertSame(400, $response->responseType());
  }

  public function testAnErrorRollsBackAndIsRethrown(): void
  {
    $trades = $this->createStub(TradeRepository::class);
    $trades->method('SaveTrade')->willThrowException(new \TypeError('Bad trade'));

    $service = $this->service(trades: $trades, transactions: $this->transactions(0, 1));

    $this->expectException(\TypeError::class);

    $service->EnterTrade($this->draft(), $this->trade());
  }
}
