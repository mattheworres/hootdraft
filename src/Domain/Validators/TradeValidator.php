<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Models\HootDraftResponse;

class TradeValidator {
  public function IsManagerValidForAssetRetrieval(Draft $draft, Manager $manager) {
    $valid = true;
    $errors = array();

    if ($draft->draft_id != $manager->draft_id) {
      $errors[] = "Manager does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsTradeValid(Draft $draft, Trade $trade) {
    $valid = true;
    $errors = array();
    $assetCount = count($trade->trade_assets);
    $uniqueAssetCount = count(array_unique($trade->trade_assets));

    if (empty($trade->trade_round) || $trade->trade_round < 0 || $trade->trade_round > $draft->draft_rounds) {
      $errors[] = "Invalid value for trade round.";
      $valid = false;
    }

    if ($assetCount == 0) {
      $errors[] = "A trade must include at least one asset.";
      $valid = false;
    }

    if ($trade->manager1->manager_id == $trade->manager2->manager_id) {
      $errors[] = "A trade must be between two different managers.";
      $valid = false;
    }

    foreach ($trade->trade_assets as $trade_asset) {
      if ($trade_asset->oldmanager_id != $trade->manager1_id
        && $trade_asset->oldmanager_id != $trade->manager2_id) {
        $player_id = $trade_asset->player->player_id;
        $errors[] = "Asset #$player_id does not belong to either manager.";
        $valid = false;
      }

      if ($trade_asset->player->draft_id != $draft->draft_id) {
        $player_id = $trade_asset->player->player_id;
        $errors[] = "Asset #$player_id does not belong to draft #$draft->draft_id";
        $valid = false;
      }
    }

    if ($assetCount != $uniqueAssetCount) {
      $errors[] = "One or more of the trade assets are duplicate.";
      $valid = false;
    }

    if ($draft->draft_id != $trade->manager1->draft_id) {
      $errors[] = "Manager 1 does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    if ($draft->draft_id != $trade->manager2->draft_id) {
      $errors[] = "Manager 2 does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }
}
