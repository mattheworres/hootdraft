<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\TradeBuildResult;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Validators\TradeValidator;

class TradeService {
  public function __construct(
    private PickRepository $picks,
    private TradeRepository $trades,
    private DraftRepository $drafts,
    private ManagerRepository $managers,
    private TradeValidator $validator,
    private TransactionService $transactions,
    private ClientTimeFormatter $timeFormatter,
  ) {
  }

  public function GetTrades(int $draft_id) {
    $trades = $this->trades->GetTrades($draft_id);

    foreach ($trades as $trade) {
      $trade->trade_time = $this->timeFormatter->MarkAsUtc($trade->trade_time);
    }

    return $trades;
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

  /**
   * Builds the Trade the commissioner entered and validates it. The result holds the valid Trade, or only the errors:
   * why the Trade could not be built, or why it is not valid.
   *
   * @param mixed $assetIds The ids of the picks to trade, as the client sent them. Anything other than an array is refused.
   */
  public function BuildTrade(Draft $draft, $manager1_id, $manager2_id, $assetIds): TradeBuildResult {
    $trade = new Trade();
    $trade->draft_id = (int) $draft->draft_id;
    $trade->manager1_id = $manager1_id;
    $trade->manager2_id = $manager2_id;
    $trade->trade_round = $draft->draft_current_round;

    try {
      if (!is_array($assetIds)) {
        throw new \Exception("Invalid trade data.");
      }

      $trade->manager1 = $this->managers->Load($manager1_id);
      $trade->manager2 = $this->managers->Load($manager2_id);

      foreach ($assetIds as $assetId) {
        $trade->trade_assets[] = $this->_BuildAsset($trade, $assetId);
      }
    } catch (\Exception $e) {
      $message = $e->getMessage();

      return TradeBuildResult::Refused(array("Unable to build trade: $message"));
    }

    $validity = $this->validator->IsTradeValid($draft, $trade);

    if (!$validity->success) {
      return TradeBuildResult::Refused($validity->errors);
    }

    return TradeBuildResult::Built($trade);
  }

  public function EnterTrade(Draft $draft, Trade $trade) {
    $response = new HootDraftResponse();

    try {
      // Swapping the picks, saving the trade and bumping the counter happen together or not at all
      $trade = $this->transactions->Run(function () use ($draft, $trade) {
        //Exchange the picks in the database
        $trade = $this->_ExchangeAssets($draft, $trade);
        //Save the trade itself
        $trade = $this->trades->SaveTrade($trade);
        //Save all assets
        $trade = $this->trades->SaveAssets($trade);
        //Update the draft's counter
        $draft->draft_counter = $this->drafts->IncrementDraftCounter($draft);

        return $trade;
      });

      $response->success = true;
      $response->trade = $trade;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  /**
   * The pick moves from the manager who owns it to the other manager in the trade
   * @throws \Exception if neither manager owns the pick
   */
  private function _BuildAsset(Trade $trade, $assetId): TradeAsset {
    $asset = new TradeAsset();
    $asset->player = $this->picks->Load($assetId);
    // Stored as 1 or 0: the trade_assets column is an integer
    $asset->was_drafted = $asset->player->HasBeenSelected() ? 1 : 0;

    $side1 = [$trade->manager1_id, $trade->manager1];
    $side2 = [$trade->manager2_id, $trade->manager2];

    [$from, $to] = match (true) {
      $asset->player->manager_id == $trade->manager1_id => [$side1, $side2],
      $asset->player->manager_id == $trade->manager2_id => [$side2, $side1],
      default => throw new \Exception("Invalid trade data."),
    };

    [$asset->oldmanager_id, $asset->oldmanager] = $from;
    [$asset->newmanager_id, $asset->newmanager] = $to;

    return $asset;
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
