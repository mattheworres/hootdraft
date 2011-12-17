<?php
/**
 * A PHPDraft "Trade" object
 * 
 * During a live draft a commissioner can facilitate a trade, which allows
 * trade assets (both drafted players and undrafted picks) to exchange hands.
 * 
 */

class trade_object {
	/**
	 * @var int The unique ID for this trade
	 */
	public $trade_id;
	
	/**
	 * @var int $draft_id The unique ID for the parent draft
	 */
	public $draft_id;
	
	/**
	 * @var int $manager1_id Used for loading from DB. Access Manager ID from manager object.
	 */
	protected $manager1_id;
	
	/**
	 * @var manager_object $manager1 The first manager in this trade 
	 */
	public $manager1;
	
	/**
	 * @var int $manager2_id Used for loading DB. Access Manager ID from manager object.
	 */
	protected $manager2_id;
	
	/**
	 * @var manager_object $manager2 The second manager in this trade 
	 */
	public $manager2;
	
	/**
	 * @var string $trade_time The timestamp of this trade 
	 */
	public $trade_time;
	
	/**
	 * @var array All assets involved in this trade 
	 */
	public $trade_assets;
	
	/**
	 * @var array Error messages from validation of asset ownership 
	 */
	private $ownership_errors;
	
	public function __construct($trade_id = 0) {
		if((int)$trade_id == 0)
			return false;
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM trades WHERE trade_id = ? LIMIT 1");
		$stmt->bindParam(1, $trade_id);
		$stmt->setFetchMode(PDO::FETCH_INTO, $this);
		
		if(!$stmt->execute())
			return false;
		
		if(!$stmt->fetch())
			return false;
		
		$this->manager1 = new manager_object($this->manager1_id);
		$this->manager2 = new manager_object($this->manager2_id);
		$this->trade_assets = trade_asset_object::GetAssetsByTrade($this->trade_id, $this->manager1, $this->manager2);
		
		if($trade->manager1 == false || $trade->manager2 == false || $trade->trade_assets == false)
			return false;
		
		return true;
	}
	
	public function saveTrade() {
		global $DBH; /* @var $DBH PDO */
		
		if($this->trade_id > 0 && $this->draft_id > 0) {//Update
			//TODO: Implement update - must think carefully about assumptions data model has about what presenter does with (the data model)
			return false;
		}elseif($this->draft_id > 0) {//Save
			//Exchange Assets
			if(!$this->ExchangeAssets())
				return false;
			
			//Save the trade
			$stmt = $DBH->prepare("INSERT INTO trades (draft_id, manager1_id, manager2_id, trade_time) VALUES (?, ?, ?, ?)");
			$stmt->bindParam(1, $this->draft_id);
			$stmt->bindParam(2, $this->manager1->manager_id);
			$stmt->bindParam(3, $this->manager2->manager_id);
			$stmt->bindParam(4, php_draft_library::getNowPhpTime());
			
			if(!$stmt->execute())
				return false;
			
			$this->trade_id = (int)$DBH->lastInsertId();
			
			//Save all of the assets
			foreach($this->trade_assets as $asset) {
				/* @var $asset trade_asset_object */
				$asset->trade_id = $this->trade_id;
				if(!$asset->saveAsset())
					return false;
			}
			
			return true;
		}else
			return false;
	}
	
	/**
	 * Get the validity of this object as it stands to ensure it can be updated as a pick
	 * @param draft_object $draft The draft this pick is being submitted for
	 * @return array $errors Array of string error messages 
	 */
	public function getValidity() {
		$errors = array();
		
		if(empty($this->draft_id) || $this->draft_id == 0)
			$errors[] = "Trade doesn't belong to a draft.";
		if(empty($this->manager1->manager_id) || $this->manager1->manager_id == 0)
			$errors[] = "Trade doesn't have a first manager.";
		if(empty($this->manager2->manager_id) || $this->manager2->manager_id == 0)
			$errors[] = "Trade doesn't have a second manager.";
		if($this->manager1->manager_id == $this->manager2->manager_id)
			$errors[] = "Trade must be between two separate managers.";
		if(empty($this->trade_assets) || count($this->trade_assets) < 2)
			$errors[] = "Trade must have at least two assets involved.";
		
		//Check to make sure each asset is truly owned by the old manager
		if(!$this->AssetOwnershipIsCorrect()) {
			foreach($this->ownership_errors as $ownership_error_message)
				$errors[] = $ownership_error_message;
		}
		
		//Ensure each manager is getting at least one asset in return.
		if(!$this->EachManagerHasOneAsset())
			$errors[] = "Trade must include each manager receiving at least one asset.";
		
		return $errors;
	}
	
	/**
	 * Get all trades that have occurred for a draft.
	 * @param int $draft_id ID of draft to get trades for
	 * @return array Trades for given draft, or false on error. 
	 */
	public function getDraftTrades($draft_id) {
		if((int)$draft_id = 0)
			return false;
		
		$trades = array();
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM trades WHERE draft_id = ? ORDER BY trade_time");
		$stmt->bindParam(1, $draft_id);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'trade_object');
		
		if(!$stmt->execute())
			return false;
		
		//For each trade pass the manager object in so we dont thrash when loading assets from DB
		while($trade = $stmt->fetch()) {
			/* @var $trade trade_object*/
			$trade->manager1 = new manager_object($trade->manager1_id);
			$trade->manager2 = new manager_object($trade->manager2_id);
			$trade->trade_assets = trade_asset_object::GetAssetsByTrade($this->trade_id, $trade->manager1, $trade->manager2);
				
			if($trade->manager1 == false || $trade->manager2 == false || $trade->trade_assets == false)
				return false;
			
			$trades[] = $trade;
		}
		
		return $trades;
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
	public static function BuildTrade($draft_id, $manager1_id, $manager2_id, $manager1PlayerIds, $manager2PlayerIds) {
		$newTrade = new trade_object();
		$newTrade->draft_id = $draft_id;
		$newTrade->manager1 = new manager_object($manager1_id);
		$newTrade->manager2 = new manager_object($manager2_id);
		$newTrade->trade_assets = trade_object::BuildTradeAssets($manager1PlayerIds, $manager2PlayerIds, $newTrade->manager1, $newTrade->manager2);
		
		return $newTrade;
	}
	
	/**
	 * Build up all of the trade asset objects for a trade
	 * @param array $manager1AssetIds
	 * @param manager_object $manager1
	 * @param manager_object $manager2
	 * @return array Array of trade asset objects, or false on failure 
	 */
	private static function BuildTradeAssets(array $manager1AssetIds, array $manager2AssetIds, manager_object $manager1, manager_object $manager2) {
		$tradeAssets = array();
		
		if($manager1 == null || $manager2 == null)
			return false;
		
		foreach($manager1AssetIds as $playerId) {
			$playerId = (int)$playerId;
			
			if($playerId == 0)
				return false;
			
			$newAsset = new trade_asset_object();
			$newAsset->oldmanager = $manager1;
			$newAsset->newmanager = $manager2;
			$newAsset->player = new player_object($playerId);
			
			if(!isset($newAsset->player) || $newAsset->player == false) {
				return false;
			}
			
			$tradeAssets[] = $newAsset;
		}
		
		foreach($manager2AssetIds as $playerId) {
			$playerId = (int)$playerId;
			
			if($playerId == 0)
				return false;
			
			$newAsset = new trade_asset_object();
			$newAsset->oldmanager = $manager2;
			$newAsset->newmanager = $manager1;
			$newAsset->player = new player_object($playerId);
			
			if(!isset($newAsset->player) || $newAsset->player == false) {
				return false;
			}
			
			$tradeAssets[] = $newAsset;
		}
		
		return $tradeAssets;
	}
	
	/**
	 * Perform the core swap of ownership of each pick in the database for a trade.
	 * @return bool True on success, false otherwise 
	 */
	private function ExchangeAssets() {
		foreach($this->trade_assets as $asset) {
			/* @var $asset trade_asset_object */
			$asset->player->manager_id = $asset->newmanager->manager_id;
			if(!$asset->player->savePlayer())
				return false;
		}
		return true;
	}
	
	/**
	 * Run through all assets involved in a potential trade and ensure they are owned by said managers.
	 * @return bool True on success, an array of error messages otherwise. 
	 */
	private function AssetOwnershipIsCorrect() {
		$manager1_current_assets = player_object::getAllPlayersByManager($this->manager1->manager_id);
		$manager2_current_assets = player_object::getAllPlayersByManager($this->manager2->manager_id);
		
		$this->ownership_errors = array();
		
		foreach($this->trade_assets as $asset) {
			/* @var $asset trade_asset_object */
			switch($asset->oldmanager->manager_id) {
				case $this->manager1->manager_id:
					if(!in_array($asset->player, $manager1_current_assets))
						$this->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by first manager.";
					break;
				
				case $this->manager2->manager_id:
					if(!in_array($asset->player, $manager2_current_assets))
						$this->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by the second manager.";
					break;
				
				default:
					$this->ownership_errors[] = "Pick #" . $asset->player->player_pick . " is not owned by either of the managers.";
					break;
			}
		}
		
		return empty($this->ownership_errors);
	}
	
	/**
	 * Checks to ensure that each manager is receiving at least one asset in the potential trade.
	 * @return bool 
	 */
	private function EachManagerHasOneAsset() {
		$manager2_has_one = false;
		$manager1_has_one = false;
		
		foreach($this->trade_assets as $asset) {
			/* @var $asset trade_asset_object */
			if($asset->newmanager == null)
				return false;
			
			switch($asset->newmanager->manager_id) {
				case $this->manager1->manager_id:
					$manager1_has_one = true;
					break;
				case $this->manager2->manager_id:
					$manager2_has_one = true;
					break;
			}
			if($manager1_has_one && $manager2_has_one)
				return true;
		}
		
		return $manager1_has_one && $manager2_has_one;
	}
}
?>
