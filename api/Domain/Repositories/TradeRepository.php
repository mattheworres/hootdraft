<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Entities\TradeAsset;
use PhpDraft\Domain\Entities\Manager;

class TradeRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetTrades($draft_id) {
    $trades = array();

    $stmt = $this->app['db']->prepare("SELECT * FROM trades WHERE draft_id = ? ORDER BY trade_time");
    $stmt->bindParam(1, $draft_id);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Trade');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load trades.");
    }

    //For each trade pass the manager object in so we dont thrash when loading assets from DB
    while ($trade = $stmt->fetch()) {
      try {
        $trade->manager1 = $this->app['phpdraft.ManagerRepository']->Load($trade->manager1_id);
        $trade->manager2 = $this->app['phpdraft.ManagerRepository']->Load($trade->manager2_id);
        $trade->trade_assets = $this->GetAssets($trade, $trade->manager1, $trade->manager2);
      } catch (\Exception $e) {
        throw new \Exception("Unable to load managers or trade assets: " . $e->getMessage());
      }

      //To help display of UTC times on client, append UTC to the end of the string
      $trade->trade_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($trade->trade_time);

      $trades[] = $trade;
    }

    return $trades;
  }

  public function GetAssets(Trade $trade, Manager $manager1, Manager $manager2) {
    $assets = array();

    $stmt = $this->app['db']->prepare("SELECT * FROM trade_assets WHERE trade_id = ?");
    $stmt->bindParam(1, $trade->trade_id);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\TradeAsset');

    if (!$stmt->execute()) {
      throw new \Exception($stmt->errorInfo());
    }

    while ($asset = $stmt->fetch()) {
      /* @var $asset trade_asset_object */
      //We must use the protected $newmanager_id and $oldmanager_id because we have just pulled from DB, objs aren't automatic:
      if ($asset->newmanager_id != $manager1->manager_id && $asset->newmanager_id != $manager2->manager_id) {
              throw new \Exception('Invalid manager ID for asset: ' . $asset->newmanager_id);
      }

      if ($asset->oldmanager_id != $manager1->manager_id && $asset->oldmanager_id != $manager2->manager_id) {
              throw new \Exception('Invalid manager ID for asset: ' . $asset->oldmanager_id);
      }

      //Use passed in manager_objects to prevent unneccessary SELECTs to the db:
      $asset->player = $this->app['phpdraft.PickRepository']->Load($asset->player_id);
      $asset->newmanager = $asset->newmanager_id == $manager1->manager_id ? $manager1 : $manager2;
      $asset->oldmanager = $asset->oldmanager_id == $manager1->manager_id ? $manager1 : $manager2;

      if ($asset->player == false || $asset->newmanager == false || $asset->oldmanager == false) {
              throw new \Exception('Invalid asset loaded.');
      }

      $assets[] = $asset;
    }

    return $assets;
  }

  public function DeleteAllTrades($draft_id) {
    $draft_trade_stmt = $this->app['db']->prepare("SELECT * FROM trades WHERE draft_id = ?");
    $draft_trade_stmt->bindParam(1, $draft_id);
    $draft_trade_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Trade');

    if (!$draft_trade_stmt->execute()) {
      throw new \Exception("Unable to delete trades for draft $draft_id");
    }

    $trades = array();

    while ($trade = $draft_trade_stmt->fetch()) {
          $trades[] = $trade;
    }

    $delete_assets_stmt = $this->app['db']->prepare("DELETE FROM trade_assets WHERE trade_id = :trade_id");
    $delete_trade_stmt = $this->app['db']->prepare("DELETE FROM trades WHERE trade_id = :trade_id");

    foreach ($trades as $trade) {
      $delete_assets_stmt->bindValue(":trade_id", $trade->trade_id);

      if (!$delete_assets_stmt->execute()) {
              throw new \Exception("Unable to delete trade assets for $draft_id.");
      }

      $delete_trade_stmt->bindValue(":trade_id", $trade->trade_id);

      if (!$delete_trade_stmt->execute()) {
              throw new \Exception("Unable to delete trade assets for $draft_id.");
      }
    }

    return;
  }

  public function SaveTrade(Trade $trade) {
    $stmt = $this->app['db']->prepare("INSERT INTO trades (draft_id, manager1_id, manager2_id, trade_round, trade_time) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())");
    $stmt->bindParam(1, $trade->draft_id);
    $stmt->bindParam(2, $trade->manager1->manager_id);
    $stmt->bindParam(3, $trade->manager2->manager_id);
    $stmt->bindParam(4, $trade->trade_round);

    if (!$stmt->execute()) {
      throw new Exception("Unable to save trade.");
    }

    $trade->trade_id = (int)$this->app['db']->lastInsertId();

    return $trade;
  }

  public function SaveAssets(Trade $trade) {
    $stmt = $this->app['db']->prepare("INSERT INTO trade_assets
      (trade_id, player_id, oldmanager_id, newmanager_id, was_drafted)
      VALUES
      (:trade_id, :player_id, :oldmanager_id, :newmanager_id, :was_drafted)");

    $stmt->bindParam(':trade_id', $trade->trade_id);

    foreach ($trade->trade_assets as &$asset) {
      $stmt->bindParam(':player_id', $asset->player->player_id);
      $stmt->bindParam(':oldmanager_id', $asset->oldmanager->manager_id);
      $stmt->bindParam(':newmanager_id', $asset->newmanager->manager_id);
      $stmt->bindParam(':was_drafted', $asset->was_drafted);

      if (!$stmt->execute()) {
        throw new Exception("Unable to save trade asset #$asset->player_id");
      }

      $asset->trade_id = $trade->trade_id;
      $asset->trade_asset_id = $this->app['db']->lastInsertId();
    }

    return $trade;
  }
}
