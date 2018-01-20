<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Entities\TradeAsset;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Validators\TradeValidator;

class TradeValidatorTest extends TestCase {
  function setUp() {
    $this->app = require dirname(__FILE__).'/../../../../api/config/_app.php';
    $this->draft = new Draft();
    $this->trade = new Trade();
    $this->trade->manager1 = new Manager();
    $this->trade->manager2 = new Manager();
    $this->sut = new TradeValidator($this->app);
  }

  public function testIsInvalidManagerIndicatedForAssetRetrieval() {
    $this->draft->draft_id = 1;

    $this->trade->manager1->draft_id = 2;

    $result = $this->sut->IsManagerValidForAssetRetrieval($this->draft, $this->trade->manager1);

    $this->assertFalse($result->success);
  }

  public function testIsValidManagerIndicatedForAssetRetrieval() {
    $this->draft->draft_id = 1;

    $this->trade->manager1->draft_id = 1;

    $result = $this->sut->IsManagerValidForAssetRetrieval($this->draft, $this->trade->manager1);

    $this->assertTrue($result->success);
  }

  public function testValidationOfTradeRoundValues() {
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

  public function testValidationOfTradeAssetOwnership() {
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

  public function testValidationOfAssetCounts() {
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

  public function testValidationOfManagersBelongingToTheDraft() {
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