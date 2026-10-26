<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\TradeRepository;

class TradeService {
  public function __construct(
    private PickRepository $picks,
    private TradeRepository $trades,
    private DraftRepository $drafts,
    private \PDO $pdo,
  ) {
  }

  public function GetManagerAssets(Draft $draft, $manager_id) {
    $response = new HootDraftResponse();

    try {
      $assets = $this->picks->LoadManagerPicks($manager_id, $draft->draft_id, $draft, false);

      $response->success = true;
      $response->manager_id = $manager_id;
      $response->assets = $assets;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function EnterTrade(Draft $draft, Trade $trade) {
    $response = new HootDraftResponse();

    try {
      // Swapping the picks, saving the trade and bumping the counter happen together or not at all
      $this->pdo->beginTransaction();

      //Exchange the picks in the database
      $trade = $this->_ExchangeAssets($draft, $trade);
      //Save the trade itself
      $trade = $this->trades->SaveTrade($trade);
      //Save all assets
      $trade = $this->trades->SaveAssets($trade);
      //Update the draft's counter
      $draft->draft_counter = $this->drafts->IncrementDraftCounter($draft);

      $this->pdo->commit();
      $this->drafts->FlushPendingCacheResets();

      $response->success = true;
      $response->trade = $trade;
    } catch (\Exception $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      $this->drafts->FlushPendingCacheResets();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  /**
   * Perform the core swap of ownership of each pick (player) in the database
   * @param Trade $trade
   * @return Trade
   * @throws \Exception
   */
  private function _ExchangeAssets(Draft $draft, Trade $trade) {
    //Update the draft counter - can be the same for all assets, doesnt matter. Trade = 1 action
    $new_counter_value = $draft->draft_counter + 1;

    foreach ($trade->trade_assets as $asset) {
      $asset->player->manager_id = $asset->newmanager->manager_id;
      $asset->player->player_counter = $new_counter_value;

      $this->picks->UpdatePick($asset->player);
    }

    return $trade;
  }
}
