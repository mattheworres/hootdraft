<?php
/**
 * Represents a PHPDraft player, or "pick" in the draft.
 * 
 * Each player is owned by a manager, who belongs to a draft.
 * 
 * Players carry draft information on them - such as which round and which pick
 * they exist at (player information will be blank if they are unchecked)
 */
class player_service {
	public function loadPlayer($id = 0) {
		$id = (int)$id;
		
		$player = new player_object();
		
		if($id == 0) {
			return $player;
		}
		
		global $DBH; /* @var $DBH PDO */
		
		$stmt = $DBH->prepare("SELECT * FROM players WHERE player_id = ? LIMIT 1");
		$stmt->bindParam(1, $id);
		
		$stmt->setFetchMode(PDO::FETCH_INTO, $player);
		
		if(!$stmt->execute()) {
			throw new Exception("Unable to load player.");
		}
		
		if(!$stmt->fetch()) {
			throw new Exception("Unable to load player.");
		}

		return $player;
	}
}
?>
