<?php

/*
 * A PHPDraft trade asset
 * 
 * A trade involves two or more assets being exchanged between managers.
 */

class trade_asset_object {

  /** @var int */
  public $trade_asset_id;

  /** @var int */
  public $trade_id;

  /** @var int */
  protected $player_id;

  /** @var int */
  protected $oldmanager_id;

  /** @var int */
  protected $newmanager_id;

  /** @var player_object */
  public $player;

  /** @var manager_object */
  public $oldmanager;

  /** @var manager_object */
  public $newmanager;

  /** @var bool Used for loading from the DB */
  protected $was_drafted;

  public function __construct($trade_asset_id = 0) {
    if ((int) $trade_asset_id == 0)
      return false;

    global $DBH; /* @var $DBH PDO */

    $stmt = $DBH->prepare("SELECT * FROM trade_assets WHERE trade_id = ? LIMIT 1");
    $stmt->bindParam(1, $trade_asset_id);
    $stmt->setFetchMode(PDO::FETCH_INTO, $this);

    if (!$stmt->execute())
      return false;

    if (!$stmt->fetch())
      return false;

    return true;
  }

  /**
   * Saves an asset to the DB. Update not supported - 
   * see saveTrade() for trade_object for reason
   * @return boolean Success
   */
  public function saveAsset() {
    global $DBH; /* @var $DBH PDO */

    if ($this->trade_asset_id > 0) {
      //TODO: implement update
      return false;
    } else {
      $wasDrafted = $this->WasDrafted() ? 1 : 0;

      $stmt = $DBH->prepare("INSERT INTO trade_assets (trade_id, player_id, oldmanager_id, newmanager_id, was_drafted) VALUES (?, ?, ?, ?, ?)");
      $stmt->bindParam(1, $this->trade_id);
      $stmt->bindParam(2, $this->player->player_id);
      $stmt->bindParam(3, $this->oldmanager->manager_id);
      $stmt->bindParam(4, $this->newmanager->manager_id);
      $stmt->bindParam(5, $wasDrafted);

      $success = $stmt->execute();
      $error_code = "blart";
      if (!$success)
        $error_code = $stmt->errorInfo();

      return $success;
    }
  }

  /**
   * Get all assets involved in a trade. Requires passing both managers to prevent
   * thrashing the database to SELECT the two same manager rows for every asset.
   * @param int $trade_id
   * @param manager_object $manager1
   * @param manager_object $manager2
   * @return array
   */
  public function GetAssetsByTrade($trade_id, manager_object $manager1, manager_object $manager2) {
    if ((int) $trade_id == 0)
      return false;
    /* @var $manager1 manager_object */
    /* @var $manager2 manager_object */
    /* @var $DBH PDO */
    global $DBH;
    $assets = array();
    $PLAYER_SERVICE = new player_service();

    $stmt = $DBH->prepare("SELECT * FROM trade_assets WHERE trade_id = ?");
    $stmt->bindParam(1, $trade_id);
    $stmt->setFetchMode(PDO::FETCH_CLASS, "trade_asset_object");

    if (!$stmt->execute()) {
      $error_info = $stmt->errorInfo();
      return false;
    }

    while ($asset = $stmt->fetch()) {
      /* @var $asset trade_asset_object */
      //We must use the protected $newmanager_id and $oldmanager_id because we have just pulled from DB, objs aren't automatic:
      if ($asset->newmanager_id != $manager1->manager_id && $asset->newmanager_id != $manager2->manager_id)
        return false;

      if ($asset->oldmanager_id != $manager1->manager_id && $asset->oldmanager_id != $manager2->manager_id)
        return false;

      //Use passed in manager_objects to prevent unneccessary SELECTs to the db:
      $asset->player = $PLAYER_SERVICE->loadPlayer($asset->player_id);
      $asset->newmanager = $asset->newmanager_id == $manager1->manager_id ? $manager1 : $manager2;
      $asset->oldmanager = $asset->oldmanager_id == $manager1->manager_id ? $manager1 : $manager2;

      if ($asset->player == false || $asset->newmanager == false || $asset->oldmanager == false)
        return false;

      $assets[] = $asset;
    }

    return $assets;
  }

  /**
   * Whether or not the player is considered "drafted" or not.
   * @return bool 
   */
  public function WasDrafted() {
    return $this->was_drafted != null ? (bool) $this->was_drafted : $this->player->hasBeenSelected();
  }

}

?>
