<?php

class trade_service {
  /**
   * Load a trade from the database
   * @global PDO $DBH
   * @param int $id
   * @return \trade_object
   * @throws Exception
   */
  public function loadTrade($trade_id = 0) {
    if ((int) $trade_id == 0) {
      throw new Exception("Unable to load trade: invalid ID.");
    }

    global $DBH; /* @var $DBH PDO */
    
    $trade = new trade_object();

    $stmt = $DBH->prepare("SELECT * FROM trades WHERE trade_id = ? LIMIT 1");
    $stmt->bindParam(1, $trade_id);
    $stmt->setFetchMode(PDO::FETCH_INTO, $trade);

    if (!$stmt->execute()) {
      throw new Exception("Unable to load trade.");
    }

    if (!$stmt->fetch()) {
      throw new Exception("Unable to load trade.");
    }

    //TODO: Move into trade service:
    $MANAGER_SERVICE = new manager_service();

    try {
      $trade->manager1 = $MANAGER_SERVICE->loadManager($trade->manager1_id);
      $trade->manager2 = $MANAGER_SERVICE->loadManager($trade->manager2_id);
      $trade->trade_assets = trade_asset_object::GetAssetsByTrade($trade->trade_id, $trade->manager1, $trade->manager2);
    }catch(Exception $e) {
      throw new Exception("Unable to load managers or trade assets: " . $e->getMessage());
    }

    return $trade;
  }
  
  /**
   * Saves a new instance of a trade. Currently update is not supported - see note in body of function.
   * @global PDO $DBH
   * @param draft_object $draft
   * @param trade_object $trade
   * @return \trade_object
   * @throws Exception
   */
  public function saveTrade(draft_object $draft, trade_object $trade) {
    global $DBH; /* @var $DBH PDO */

    if ($trade->trade_id > 0 && $trade->draft_id > 0) {//Update
      //TODO: Implement update - must think carefully about assumptions data model has about what presenter does with (the data model)
      throw new Exception("Update not implemented.");
    } elseif ($trade->draft_id > 0) {//Save
      //Exchange Assets
      try {
        $this->ExchangeAssets($draft, $trade);
      }catch(Exception $e) {
        throw new Exception("Unable to exchange assets: " . $e->getMessage());
      }

      //Save the trade
      $stmt = $DBH->prepare("INSERT INTO trades (draft_id, manager1_id, manager2_id, trade_time) VALUES (?, ?, ?, ?)");
      $stmt->bindParam(1, $trade->draft_id);
      $stmt->bindParam(2, $trade->manager1->manager_id);
      $stmt->bindParam(3, $trade->manager2->manager_id);
      $stmt->bindParam(4, php_draft_library::getNowPhpTime());

      if (!$stmt->execute()) {
        throw new Exception("Unable to save trade.");
      }

      $trade->trade_id = (int) $DBH->lastInsertId();

      //Save all of the assets
      foreach ($trade->trade_assets as $asset) {
        /* @var $asset trade_asset_object */
        $asset->trade_id = $trade->trade_id;
        
        try {
          $this->saveAsset($asset);
        }catch(Exception $e) {
          throw new Exception("Unable to save trade asset: " . $e->getMessage());
        }
      }

      return $trade;
    }
    else
      throw new Exception("Invalid state to save trade.");
  }
  
  /**
   * Perform the core swap of ownership of each pick in the database for a trade.
   * @param draft_object $draft
   * @param trade_object $trade
   * @return \trade_object
   * @throws Exception
   */
  public function ExchangeAssets(draft_object $draft, trade_object $trade) {
    $PLAYER_SERVICE = new player_service();
    //Update the draft counter - can be the same for all assets, doesnt matter. Trade = 1 action
    $new_counter_value = $draft->draft_counter + 1;
    
    foreach ($trade->trade_assets as $asset) {
      /* @var $asset trade_asset_object */
      $asset->player->manager_id = $asset->newmanager->manager_id;
      $asset->player->player_counter = $new_counter_value;

      try {
        $PLAYER_SERVICE->savePlayer($asset->player);
      } catch (Exception $e) {
        throw new Exception("Unable to exchange players for trade: " . $e->getMessage());
      }
    }
    return $trade;
  }
  
  /**
   * Saves an asset to the DB. Update not supported - see saveTrade() for trade_object for reason
   * @global PDO $DBH
   * @param type $trade_asset
   * @return type
   * @throws Exception
   */
  public function saveAsset($trade_asset) {
    global $DBH; /* @var $DBH PDO */

    if ($trade_asset->trade_asset_id > 0) {
      //TODO: implement update
      throw new Exception("Trade asset update not implemented.");
    } else {
      $wasDrafted = $trade_asset->WasDrafted() ? 1 : 0;

      $stmt = $DBH->prepare("INSERT INTO trade_assets (trade_id, player_id, oldmanager_id, newmanager_id, was_drafted) VALUES (?, ?, ?, ?, ?)");
      $stmt->bindParam(1, $trade_asset->trade_id);
      $stmt->bindParam(2, $trade_asset->player->player_id);
      $stmt->bindParam(3, $trade_asset->oldmanager->manager_id);
      $stmt->bindParam(4, $trade_asset->newmanager->manager_id);
      $stmt->bindParam(5, $wasDrafted);

      if(!$stmt->execute()) {
        throw new Exception("Unable to save trade asset.");
      }

      return $trade_asset;
    }
  }
  
  /**
   * Get the validity of this object as it stands to ensure it can be updated as a pick
   * @param draft_object $draft The draft this pick is being submitted for
   * @return array $errors Array of string error messages 
   */
  public function getValidity($trade) {
    $errors = array();

    if (empty($trade->draft_id) || $trade->draft_id == 0)
      $errors[] = "Trade doesn't belong to a draft.";
    if (empty($trade->manager1->manager_id) || $trade->manager1->manager_id == 0)
      $errors[] = "Trade doesn't have a first manager.";
    if (empty($trade->manager2->manager_id) || $trade->manager2->manager_id == 0)
      $errors[] = "Trade doesn't have a second manager.";
    if ($trade->manager1->manager_id == $trade->manager2->manager_id)
      $errors[] = "Trade must be between two separate managers.";
    if (empty($trade->trade_assets) || count($trade->trade_assets) < 2)
      $errors[] = "Trade must have at least two assets involved.";

    //Check to make sure each asset is truly owned by the old manager
    if (!$this->AssetOwnershipIsCorrect($trade)) {
      $errors[] = "One or more errors with asset ownership were found.";
    }

    //Ensure each manager is getting at least one asset in return.
    if (!$this->EachManagerHasOneAsset($trade))
      $errors[] = "Trade must include each manager receiving at least one asset.";

    return $errors;
  }
  
  /**
   * Get all trades that have occurred for a draft.
   * @param int $draft_id ID of draft to get trades for
   * @return array Trades for given draft, or false on error. 
   */
  public function getDraftTrades($draft_id) {
    if ((int) $draft_id == 0) {
      throw new Exception("Invalid draft id.");
    }

    $trades = array();

    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT * FROM trades WHERE draft_id = ? ORDER BY trade_time");
    $stmt->bindParam(1, $draft_id);

    $stmt->setFetchMode(PDO::FETCH_CLASS, 'trade_object');

    if (!$stmt->execute()) {
      throw new Exception("Unable to load trades.");
    }

    $MANAGER_SERVICE = new manager_service();

    //For each trade pass the manager object in so we dont thrash when loading assets from DB
    while ($trade = $stmt->fetch()) {
      /* @var $trade trade_object */
      try {
        $trade->manager1 = $MANAGER_SERVICE->loadManager($trade->manager1_id);
        $trade->manager2 = $MANAGER_SERVICE->loadManager($trade->manager2_id);
        $trade->trade_assets = trade_asset_object::GetAssetsByTrade($trade->trade_id, $trade->manager1, $trade->manager2);
      }catch(Exception $e) {
        throw new Exception("Unable to load managers or trade assets: " . $e->getMessage());
      }

      $trades[] = $trade;
    }

    return $trades;
  }
  
  /**
   * Used to get the portion of assets that currently belong to given manager. Does local search on object (doesn't hit DB)
   * @param type $manager_id
   * @param trade_object $trade
   * @return type
   * @throws Exception
   */
  public function getTradeManagerAssets($manager_id, trade_object $trade) {
    $manager_number = (int) $manager_id;

    if ($manager_number == 0) {
      throw new Exception("Invalid manager id.");
    }

    $manager_assets = array();

    foreach ($trade->trade_assets as $asset) {
      /* @var $asset trade_asset_object */
      if ($asset->newmanager->manager_id == $manager_id)
        $manager_assets[] = $asset;
    }

    return $manager_assets;
  }
  
  /**
   * Builds a new trade object (must be validated separately!)
   * @param type $draft_id
   * @param type $manager1_id
   * @param type $manager2_id
   * @param type $manager1PlayerIds
   * @param type $manager2PlayerIds
   * @return trade_object 
   */
  public function BuildTrade($draft_id, $manager1_id, $manager2_id, $manager1PlayerIds, $manager2PlayerIds) {
    $MANAGER_SERVICE = new manager_service();

    $newTrade = new trade_object();
    $newTrade->draft_id = $draft_id;
    $newTrade->manager1 = $MANAGER_SERVICE->loadManager($manager1_id);
    $newTrade->manager2 = $MANAGER_SERVICE->loadManager($manager2_id);
    $newTrade->trade_assets = $this->BuildTradeAssets($manager1PlayerIds, $manager2PlayerIds, $newTrade->manager1, $newTrade->manager2);

    return $newTrade;
  }
  
  /**
   * Delete all trades and assets associated with said trades for a single draft.
   * @global PDO $DBH
   * @param type $draft_id
   * @return type
   * @throws Exception
   */
  public function DeleteTradesByDraft($draft_id) {
    $draft_id = (int) $draft_id;

    if ($draft_id == 0) {
      throw new Exception("Unable to delete trades, invalid draft ID");
    }

    global $DBH; /* @var $DBH PDO */

    $draft_trade_stmt = $DBH->prepare("SELECT * FROM trades WHERE draft_id = ?");

    $draft_trade_stmt->bindParam(1, $draft_id);

    $draft_trade_stmt->setFetchMode(PDO::FETCH_CLASS, "trade_object");

    if (!$draft_trade_stmt->execute())
      throw new Exception("Unable to grab draft to delete.");

    $trades = array();

    while ($newTrade = $draft_trade_stmt->fetch())
      $trades[] = $newTrade;

    $delete_assets_stmt = $DBH->prepare("DELETE FROM trade_assets WHERE trade_id = :trade_id");
    $delete_trade_stmt = $DBH->prepare("DELETE FROM trades WHERE trade_id = :trade_id");

    foreach ($trades as $trade) {
      /* @var $trade trade_object */
      $delete_assets_stmt->bindValue(":trade_id", $trade->trade_id);

      if (!$delete_assets_stmt->execute())
        throw new Exception("Unable to delete trade assets.");

      $delete_trade_stmt->bindValue(":trade_id", $trade->trade_id);

      if (!$delete_trade_stmt->execute())
        throw new Exception("Unable to delete trade assets.");
    }

    return;
  }
  
  /**
   * Build up all of the trade asset objects for a trade
   * @param array $manager1AssetIds
   * @param manager_object $manager1
   * @param manager_object $manager2
   * @return array Array of trade asset objects, or false on failure 
   */
  private function BuildTradeAssets(array $manager1AssetIds, array $manager2AssetIds, manager_object $manager1, manager_object $manager2) {
    $tradeAssets = array();
    $PLAYER_SERVICE = new player_service();

    if ($manager1 == null || $manager2 == null)
      throw new Exception("Unable to build trade assets - one or more managers were null.");

    foreach ($manager1AssetIds as $playerId) {
      $playerId = (int) $playerId;

      if ($playerId == 0)
        throw new Exception("Player ID null");

      $newAsset = new trade_asset_object();
      $newAsset->oldmanager = $manager1;
      $newAsset->newmanager = $manager2;
      $newAsset->player = $PLAYER_SERVICE->loadPlayer($playerId);

      if (!isset($newAsset->player) || $newAsset->player == false) {
        throw new Exception("Assets do not have players.");
      }

      $tradeAssets[] = $newAsset;
    }

    foreach ($manager2AssetIds as $playerId) {
      $playerId = (int) $playerId;

      if ($playerId == 0)
        throw new Exception("Invalid player ID");

      $newAsset = new trade_asset_object();
      $newAsset->oldmanager = $manager2;
      $newAsset->newmanager = $manager1;
      $newAsset->player = $PLAYER_SERVICE->loadPlayer($playerId);

      if (!isset($newAsset->player) || $newAsset->player == false) {
        throw new Exception("Assets do not have players.");
      }

      $tradeAssets[] = $newAsset;
    }

    return $tradeAssets;
  }
  
  /**
   * Run through all assets involved in a potential trade and ensure they are owned by said managers.
   * @return boolean True on success, an array of error messages otherwise. 
   */
  private function AssetOwnershipIsCorrect($trade) {
    $PLAYER_SERVICE = new player_service();

    $manager1_current_assets = $PLAYER_SERVICE->getAllPlayersByManager($trade->manager1->manager_id);
    $manager2_current_assets = $PLAYER_SERVICE->getAllPlayersByManager($trade->manager2->manager_id);

    $trade->ownership_errors = array();

    foreach ($trade->trade_assets as $asset) {
      /* @var $asset trade_asset_object */
      switch ($asset->oldmanager->manager_id) {
        case $trade->manager1->manager_id:
          if (!in_array($asset->player, $manager1_current_assets))
            $trade->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by first manager.";
          break;

        case $trade->manager2->manager_id:
          if (!in_array($asset->player, $manager2_current_assets))
            $trade->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by the second manager.";
          break;

        default:
          $trade->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by either of the managers.";
          break;
      }
    }

    return empty($trade->ownership_errors);
  }
  
  /**
   * Checks to ensure that each manager is receiving at least one asset in the potential trade.
   * @return boolean Success
   */
  private function EachManagerHasOneAsset($trade) {
    $manager2_has_one = false;
    $manager1_has_one = false;

    foreach ($trade->trade_assets as $asset) {
      /* @var $asset trade_asset_object */
      if ($asset->newmanager == null)
        return false;

      switch ($asset->newmanager->manager_id) {
        case $trade->manager1->manager_id:
          $manager1_has_one = true;
          break;
        case $trade->manager2->manager_id:
          $manager2_has_one = true;
          break;
      }
      if ($manager1_has_one && $manager2_has_one)
        return true;
    }

    return $manager1_has_one && $manager2_has_one;
  }
}

?>
