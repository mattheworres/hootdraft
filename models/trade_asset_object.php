<?php

/*
 * A PHPDraft trade asset
 * 
 * A trade involves two or more assets being exchanged between managers.
 */

class trade_asset_object {
	// <editor-fold defaultstate="collapsed" desc="Properties">
	/**
	 * @var int 
	 */
	public $trade_asset_id;
	/**
	 * @var int 
	 */
	public $trade_id;
	/**
	 * @var int Used for loading from the DB
	 */
	protected $player_id;
	/**
	 * @var player_object 
	 */
	public $player;
	/**
	 * @var int Used for loading from the DB
	 */
	protected $oldmanager_id;
	/**
	 * @var manager_object 
	 */
	public $oldmanager;
	/**
	 * @var int Used for loading from the DB
	 */
	protected $newmanager_id;
	/**
	 * @var manager_object 
	 */
	public $newmanager;
	/**
	 *@var bool Used for loading from the DB
	 */
	protected $was_drafted;
	
	public function __construct($trade_asset_id = 0) {
		if((int)$trade_asset_id == 0)
			return false;
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM trade_assets WHERE trade_id = ? LIMIT 1");
		$stmt->bindParam(1, $trade_asset_id);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return false;
		
		if(!$stmt->fetch())
			return false;
		
		return true;
	}
	
	public function saveAsset() {
		global $DBH; /* @var $DBH PDO */
		
		if($this->trade_asset_id > 0) {
			//TODO: implement update
			return false;
		}else {
			$stmt = $DBH->prepare("INSERT INTO trade_assets (trade_id, oldmanager_id, newmanager_id, player_id, was_drafted) VALUES (?, ?, ?, ?, ?)");
			$stmt->bindParam(1, $this->trade_id);
			$stmt->bindParam(2, $this->oldmanager->manager_id);
			$stmt->bindParam(3, $this->newmanager->manager_id);
			$stmt->bindParam(4, $this->player_id);
			$stmt->bindParam(5, $this->WasDrafted());
			
			return $stmt->execute();
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
	public function GetAssetsByTrade($trade_id, $manager1, $manager2) {
		if((int)$trade_id == 0)
			return false;
		/* @var $manager1 manager_object */
		/* @var $manager2 manager_object */
		/* @var $DBH PDO */
		global $DBH;
		$assets = array();
		
		$stmt = $DBH->prepare("SELECT * FROM trade_assets WHERE trade_id = ?");
		$stmt->bindParam(1, $trade_id);
		$stmt->setFetchMode(PDO::FETCH_CLASS);
		
		if(!$stmt->execute())
			return false;
		
		while($asset = $stmt->fetch()) {
			/* @var $asset trade_asset_object */
			if($asset->newmanager->manager_id != $manager1->manager_id || $asset->newmanager->manager_id != $manager2->manager_id)
				return false;
			
			if($asset->oldmanager->manager_id != $manager1->manager_id || $asset->oldmanager->manager_id != $manager2->manager_id)
				return false;
			
			//Use passed in manager_objects to prevent unneccessary SELECTs to the db:
			$asset->player = new player_object($asset->player_id);
			$asset->newmanager = $asset->newmanager_id == $manager1->manager_id ? $manager1 : $manager2;
			$asset->oldmanager = $asset->oldmanager_id == $manager1->manager_id ? $manager1 : $manager2;
			$asset->was_drafted = $asset->player->hasBeenSelected();
			
			if($asset->player == false || $asset->newmanager == false || $asset->oldmanager == false)
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
		return isset($this->was_drafted) && $this->was_drafted != null ? $this->was_drafted : $this->player->hasBeenSelected();
	}
}
?>
