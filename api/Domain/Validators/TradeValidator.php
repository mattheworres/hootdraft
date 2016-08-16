<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsManagerValidForAssetRetrieval(Draft $draft, Manager $manager) {
    $valid = true;
    $errors = array();

    if($draft->draft_id != $manager->draft_id) {
      $errors[] = "Manager does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsTradeValid(Draft $draft, Trade $trade) {
    $valid = true;
    $errors = array();
    $assetCount = count($trade->trade_assets);
    $uniqueAssetCount = count(array_unique($trade->trade_assets));

    if(empty($trade->trade_round) || $trade->trade_round < 0 || $trade->trade_round > $draft->draft_rounds) {
      $errors[] = "Invalid value for trade round.";
      $valid = false;
    }

    foreach($trade->trade_assets as $trade_asset) {
      if($trade_asset->oldmanager_id != $trade->manager1_id 
        || $trade_asset->oldmanager_id != $trade->manager2_id) {
        $player_id = $trade_asset->player->player_id;
        $errors[] = "Asset #$player_id does not belong to either manager.";
        $valid = false;
      }

      if($trade_asset->player->draft_id != $draft->draft_id) {
        $player_id = $trade_asset->player->player_id;
        $errors[] = "Asset #$player_id does not belong to draft #$draft->draft_id";
        $valid = false;
      }
    }

    if($assetCount != $uniqueAssetCount) {
      $errors[] = "One or more of the trade assets are duplicate.";
      $valid = false;
    }

    if($draft->draft_id != $trade->manager1->draft_id) {
      $errors[] = "Manager 1 does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    if($draft->draft_id != $trade->manager2->draft_id) {
      $errors[] = "Manager 2 does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}