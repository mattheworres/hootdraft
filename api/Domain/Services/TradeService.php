<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Entities\TradeAsset;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }
  
  public function GetManagerAssets(Draft $draft, $manager_id) {
    $response = new PhpDraftResponse();

    try {
      $assets = $this->app['phpdraft.PickRepository']->LoadManagerPicks($manager_id, $draft, false);

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
    $response = new PhpDraftResponse();

    try {
      //Exchange the picks in the database
      $trade = $this->_ExchangeAssets($draft, $trade);
      //Save the trade itself
      $trade = $this->app['phpdraft.TradeRepository']->SaveTrade($trade);
      //Save all assets
      $trade = $this->app['phpdraft.TradeRepository']->SaveAssets($trade);
      //Update the draft's counter
      $draft->draft_counter = $this->app['phpdraft.DraftRepository']->IncrementDraftCounter($draft);

      $response->success = true;
      $response->trade = $trade;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  /**
   * Perform the core swap of ownership of each pick (player) in the database
   * @param Trade $trade
   * @return Trade
   * @throws Exception
   */
  private function _ExchangeAssets(Draft $draft, Trade $trade) {
    //Update the draft counter - can be the same for all assets, doesnt matter. Trade = 1 action
    $new_counter_value = $draft->draft_counter + 1;
    
    foreach ($trade->trade_assets as $asset) {
      $asset->player->manager_id = $asset->newmanager->manager_id;
      $asset->player->player_counter = $new_counter_value;

      $this->app['phpdraft.PickRepository']->UpdatePick($asset->player);
    }

    return $trade;
  }
}