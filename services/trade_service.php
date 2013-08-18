<?php

class trade_service {
  /**
   * Load a trade from the database
   * @global PDO $DBH
   * @param int $id
   * @return \trade_object
   * @throws Exception
   */
  public function loadTrade($id = 0) {
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

    $trade->manager1 = $MANAGER_SERVICE->loadManager($trade->manager1_id);
    $trade->manager2 = $MANAGER_SERVICE->loadManager($trade->manager2_id);
    $trade->trade_assets = trade_asset_object::GetAssetsByTrade($trade->trade_id, $trade->manager1, $trade->manager2);

    if ($trade->manager1 == false || $trade->manager2 == false || $trade->trade_assets == false) {
      throw new Exception("Unable to load managers or trade assets.");
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
      if (!$trade->ExchangeAssets($draft)) {
        throw new Exception("Unable to exchange assets.");
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
        if (!$asset->saveAsset()) {
          throw new Exception("Unable to save trade asset.");
        }
      }

      return $trade;
    }
    else
      throw new Exception("Invalid state to save trade.");
  }
}

?>
