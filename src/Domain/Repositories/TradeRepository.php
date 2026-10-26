<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Entities\Trade;
use HootDraft\Domain\Entities\TradeAsset;
use HootDraft\Domain\Services\UtilityService;

class TradeRepository {
  public function __construct(
    private \PDO $pdo,
    private ManagerRepository $managers,
    private PickRepository $picks,
    private UtilityService $utility,
  ) {
  }

  public function GetTrades($draft_id) {
    $trades = array();

    $stmt = $this->pdo->prepare("SELECT * FROM trades WHERE draft_id = ? ORDER BY trade_time");
    $stmt->bindParam(1, $draft_id);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, Trade::class);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load trades.");
    }

    //Load the draft's managers once, rather than twice per trade
    $draft_managers = array();

    foreach ($this->managers->GetManagersByDraftOrder($draft_id) as $manager) {
      $draft_managers[$manager->manager_id] = $manager;
    }

    //For each trade pass the manager object in so we dont thrash when loading assets from DB
    while ($trade = $stmt->fetch()) {
      try {
        $trade->manager1 = $draft_managers[$trade->manager1_id] ?? $this->managers->Load($trade->manager1_id);
        $trade->manager2 = $draft_managers[$trade->manager2_id] ?? $this->managers->Load($trade->manager2_id);
        $trade->trade_assets = $this->GetAssets($trade, $trade->manager1, $trade->manager2);
      } catch (\Exception $e) {
        throw new \Exception("Unable to load managers or trade assets: " . $e->getMessage());
      }

      //To help display of UTC times on client, append UTC to the end of the string
      $trade->trade_time = $this->utility->ConvertTimeForClientDisplay($trade->trade_time);

      $trades[] = $trade;
    }

    return $trades;
  }

  public function GetAssets(Trade $trade, Manager $manager1, Manager $manager2) {
    $assets = array();

    $stmt = $this->pdo->prepare("SELECT * FROM trade_assets WHERE trade_id = ?");
    $stmt->bindParam(1, $trade->trade_id);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, TradeAsset::class);

    if (!$stmt->execute()) {
      throw new \Exception(implode(":", $stmt->errorInfo()));
    }

    while ($asset = $stmt->fetch()) {
      /* @var $asset TradeAsset */
      //We must use the protected $newmanager_id and $oldmanager_id because we have just pulled from DB, objs aren't automatic:
      if ($asset->newmanager_id != $manager1->manager_id && $asset->newmanager_id != $manager2->manager_id) {
        throw new \Exception('Invalid manager ID for asset: ' . $asset->newmanager_id);
      }

      if ($asset->oldmanager_id != $manager1->manager_id && $asset->oldmanager_id != $manager2->manager_id) {
        throw new \Exception('Invalid manager ID for asset: ' . $asset->oldmanager_id);
      }

      //Use passed in manager_objects to prevent unneccessary SELECTs to the db:
      $asset->player = $this->picks->Load($asset->player_id);
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
    // Assets first, they reference their trade
    $delete_assets_stmt = $this->pdo->prepare("DELETE FROM trade_assets WHERE trade_id IN (SELECT trade_id FROM trades WHERE draft_id = ?)");
    $delete_assets_stmt->bindParam(1, $draft_id);

    if (!$delete_assets_stmt->execute()) {
      throw new \Exception("Unable to delete trade assets for $draft_id.");
    }

    $delete_trades_stmt = $this->pdo->prepare("DELETE FROM trades WHERE draft_id = ?");
    $delete_trades_stmt->bindParam(1, $draft_id);

    if (!$delete_trades_stmt->execute()) {
      throw new \Exception("Unable to delete trades for $draft_id.");
    }
  }

  public function SaveTrade(Trade $trade) {
    $stmt = $this->pdo->prepare("INSERT INTO trades (draft_id, manager1_id, manager2_id, trade_round, trade_time) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())");
    $stmt->bindParam(1, $trade->draft_id);
    $stmt->bindParam(2, $trade->manager1->manager_id);
    $stmt->bindParam(3, $trade->manager2->manager_id);
    $stmt->bindParam(4, $trade->trade_round);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to save trade.");
    }

    $trade->trade_id = (int)$this->pdo->lastInsertId();

    return $trade;
  }

  public function SaveAssets(Trade $trade) {
    $stmt = $this->pdo->prepare("INSERT INTO trade_assets
      (trade_id, player_id, oldmanager_id, newmanager_id, was_drafted)
      VALUES
      (:trade_id, :player_id, :oldmanager_id, :newmanager_id, :was_drafted)");

    $stmt->bindParam(':trade_id', $trade->trade_id);

    foreach ($trade->trade_assets as $asset) {
      $stmt->bindParam(':player_id', $asset->player->player_id);
      $stmt->bindParam(':oldmanager_id', $asset->oldmanager->manager_id);
      $stmt->bindParam(':newmanager_id', $asset->newmanager->manager_id);
      $stmt->bindParam(':was_drafted', $asset->was_drafted);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to save trade asset #" . $asset->player->player_id);
      }

      $asset->trade_id = $trade->trade_id;
      $asset->tradeasset_id = $this->pdo->lastInsertId();
    }

    return $trade;
  }
}
