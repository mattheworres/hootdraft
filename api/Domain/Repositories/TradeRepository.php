<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Entities\TradeAsset;

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
        $trade->trade_assets = $this->GetAssets($trade->trade_id, $trade->manager1, $trade->manager2);
      }catch(\Exception $e) {
        throw new \Exception("Unable to load managers or trade assets: " . $e->getMessage());
      }

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
      if ($asset->newmanager_id != $manager1->manager_id && $asset->newmanager_id != $manager2->manager_id)
        throw new \Exception('Invalid manager ID for asset: ' . $asset->newmanager_id);

      if ($asset->oldmanager_id != $manager1->manager_id && $asset->oldmanager_id != $manager2->manager_id)
        throw new \Exception('Invalid manager ID for asset: ' . $asset->oldmanager_id);

      //Use passed in manager_objects to prevent unneccessary SELECTs to the db:
      $asset->player = $this->app['phpdraft.PickRepository']->Load($asset->player_id);
      $asset->newmanager = $asset->newmanager_id == $manager1->manager_id ? $manager1 : $manager2;
      $asset->oldmanager = $asset->oldmanager_id == $manager1->manager_id ? $manager1 : $manager2;

      if ($asset->player == false || $asset->newmanager == false || $asset->oldmanager == false)
        throw new \Exception('Invalid asset loaded.');

      $assets[] = $asset;
    }

    return $assets;
  }
}