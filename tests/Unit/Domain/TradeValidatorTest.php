<?php
namespace HootDraft\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Validators\TradeValidator;

class TradeValidatorTest extends TestCase {
  private Draft $draft;
  private Trade $trade;
  private TradeValidator $sut;

  protected function setUp(): void {
    $this->draft = new Draft();
    $this->trade = new Trade();
    $this->trade->manager1 = new Manager();
    $this->trade->manager2 = new Manager();
    $this->sut = new TradeValidator();
  }

  public function testIsInvalidManagerIndicatedForAssetRetrieval(): void {
    $this->draft->draft_id = 1;

    $this->trade->manager1->draft_id = 2;

    $result = $this->sut->IsManagerValidForAssetRetrieval($this->draft, $this->trade->manager1);

    $this->assertFalse($result->success);
  }

  public function testIsValidManagerIndicatedForAssetRetrieval(): void {
    $this->draft->draft_id = 1;

    $this->trade->manager1->draft_id = 1;

    $result = $this->sut->IsManagerValidForAssetRetrieval($this->draft, $this->trade->manager1);

    $this->assertTrue($result->success);
  }

  public function testValidationOfTradeRoundValues(): void {
    $this->draft->draft_rounds = 10;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Invalid value for trade round.", $result->errors);

    $this->trade->trade_round = -1;
    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Invalid value for trade round.", $result->errors);

    $this->trade->trade_round = 11;
    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Invalid value for trade round.", $result->errors);

    $this->trade->trade_round = 9;
    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertNotContains("Invalid value for trade round.", $result->errors);
  }

  public function testValidationOfTradeAssetOwnership(): void {
    $this->draft->draft_id = 1;

    $tradeAsset1 = new TradeAsset();
    $tradeAsset1->player = new Pick();
    $tradeAsset1->player->player_id = 1;
    $tradeAsset1->player->draft_id = 1;

    $tradeAsset2 = new TradeAsset();
    $tradeAsset2->player = new Pick();
    $tradeAsset2->player->player_id = 2;
    $tradeAsset2->player->draft_id = 1;

    $tradeAsset1->oldmanager_id = 3;
    $tradeAsset2->oldmanager_id = 2;

    $this->trade->manager1_id = 1;
    $this->trade->manager2_id = 2;

    $this->trade->trade_assets[] = $tradeAsset1;
    $this->trade->trade_assets[] = $tradeAsset2;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Asset #1 does not belong to either manager.", $result->errors);
    //Added since the addition of an AND condition instead of OR in the validator:
    $this->assertNotContains("Asset #2 does not belong to either manager.", $result->errors);

    $tradeAsset1->oldmanager_id = 1;
    $tradeAsset2->oldmanager_id = 4;

    $this->trade->trade_assets = array();
    $this->trade->trade_assets[] = $tradeAsset1;
    $this->trade->trade_assets[] = $tradeAsset2;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Asset #2 does not belong to either manager.", $result->errors);

    $tradeAsset1->player->draft_id = 2;

    $this->trade->trade_assets = array();
    $this->trade->trade_assets[] = $tradeAsset1;
    $this->trade->trade_assets[] = $tradeAsset2;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Asset #1 does not belong to draft #1", $result->errors);

    $tradeAsset1->player->draft_id = 1;
    $tradeAsset2->player->draft_id = 38;

    $this->trade->trade_assets = array();
    $this->trade->trade_assets[] = $tradeAsset1;
    $this->trade->trade_assets[] = $tradeAsset2;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Asset #2 does not belong to draft #1", $result->errors);
  }

  public function testATradeNeedsAssetsAndTwoDifferentManagers(): void {
    $this->trade->manager1->manager_id = 5;
    $this->trade->manager2->manager_id = 5;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertContains("A trade must include at least one asset.", $result->errors);
    $this->assertContains("A trade must be between two different managers.", $result->errors);

    $this->trade->manager2->manager_id = 6;
    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertNotContains("A trade must be between two different managers.", $result->errors);
  }

  public function testValidationOfAssetCounts(): void {
    $this->draft->draft_id = 1;

    $tradeAsset1 = new TradeAsset();
    $tradeAsset1->player = new Pick();
    $tradeAsset1->player->player_id = 2;
    $tradeAsset1->player->draft_id = 1;

    $tradeAsset2 = new TradeAsset();
    $tradeAsset2->player = new Pick();
    $tradeAsset2->player->player_id = 2;
    $tradeAsset2->player->draft_id = 1;

    $tradeAsset1->oldmanager_id = 3;
    $tradeAsset2->oldmanager_id = 2;

    $this->trade->manager1_id = 1;
    $this->trade->manager2_id = 2;

    $this->trade->trade_assets[] = $tradeAsset1;
    $this->trade->trade_assets[] = $tradeAsset2;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("One or more of the trade assets are duplicate.", $result->errors);
  }

  public function testValidationOfManagersBelongingToTheDraft(): void {
    $this->draft->draft_id = 1;
    $this->trade->manager1->draft_id = 2;
    $this->trade->manager2->draft_id = 1;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Manager 1 does not belong to draft #1", $result->errors);

    $this->trade->manager1->draft_id = 1;
    $this->trade->manager2->draft_id = 3;

    $result = $this->sut->IsTradeValid($this->draft, $this->trade);

    $this->assertFalse($result->success);
    $this->assertContains("Manager 2 does not belong to draft #1", $result->errors);
  }
}