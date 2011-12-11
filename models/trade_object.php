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
	 * @var int $manager1_id
	 */
	protected $manager1_id;
	
	/**
	 * @var manager_object $manager1 The first manager in this trade 
	 */
	public $manager1;
	
	/**
	 * @var int $manager2_id
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
	 *
	 * @var array All assets involved in this trade 
	 */
	public $trade_assets;
	
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
		
		return true;
	}
	
	public function saveTrade() {
		//TODO: Implement.
	}
	
	/**
	 * Get all trades that have occurred for a draft.
	 * @param int $draft_id ID of draft to get trades for
	 * @return array Trades for given draft, or false on error. 
	 */
	public function GetDraftTrades($draft_id) {
		if((int)$draft_id = 0)
			return false;
		
		$trades = array();
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM trades WHERE draft_id = ? ORDER BY trade_time");
		$stmt->bindParam(1, $draft_id);
		
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'trade_object');
		
		if(!$stmt->execute())
			return false;
		
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
}
?>
