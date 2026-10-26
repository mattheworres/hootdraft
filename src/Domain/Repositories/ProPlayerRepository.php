<?php
namespace HootDraft\Domain\Repositories;

use HootDraft\Domain\Entities\ProPlayer;

class ProPlayerRepository {
  public function __construct(private \PDO $pdo) {
  }

  /**
   * Search players for autocomplete feature on pro_players table
   * @param string $league Required - the league to search on
   * @param string $first Player first name search term
   * @param string $last Player last name search term
   * @param string $team Player team (abbreviation)
   * @param string $position Player position (abbreviation)
   * @return array
   */
  public function SearchPlayersManual($league, $first = null, $last = null, $team = null, $position = null) {
    //Approach taken from: http://stackoverflow.com/a/4540085/324527

    $searchSql = "SELECT * FROM pro_players WHERE league = :league";

    $searchParams = array();
    $regularParams = array();

    if ($first !== null) {
      $searchParams['first_name'] = $first;
    }

    if ($last !== null) {
      $searchParams['last_name'] = $last;
    }

    if ($team !== null) {
      $regularParams['team'] = $team;
    }

    if ($position !== null) {
      $regularParams['position'] = $position;
    }

    //Finish building PDO SQL with dynamic values:
    foreach ($searchParams as $key => $value) {
      $searchSql .= sprintf(' AND %s LIKE :%s', $key, $key);
    }

    foreach ($regularParams as $key => $value) {
      $searchSql .= sprintf(' AND %s = :%s', $key, $key);
    }

    //Limit the amount of searches to just 15 to cut down on unnecessary network traffic
    $searchSql .= ' LIMIT 15';

    $stmt = $this->pdo->prepare($searchSql);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, ProPlayer::class);
    $stmt->bindValue(':league', $league);

    //Assign values to those parameters:
    foreach ($searchParams as $key => $value) {
      $stmt->bindValue(':' . $key, "%" . $value . "%");
    }

    foreach ($regularParams as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    $stmt->execute();

    $foundPlayers = array();

    while ($newPlayer = $stmt->fetch()) {
      $foundPlayers[] = $newPlayer;
    }

    return $foundPlayers;
  }

  public function SearchPlayers($league, $searchTerm) {
    //Limit the amount of searches to just 15 to cut down on unnecessary network traffic
    $stmt = $this->pdo->prepare("SELECT * FROM pro_players WHERE league = :league AND (first_name LIKE :search_term OR last_name LIKE :search_term) LIMIT 15");
    $stmt->setFetchMode(\PDO::FETCH_CLASS, ProPlayer::class);
    $stmt->bindValue(':league', $league);
    $stmt->bindValue(':search_term', "%" . $searchTerm . "%");

    $stmt->execute();

    $foundPlayers = array();

    while ($newPlayer = $stmt->fetch()) {
      $foundPlayers[] = $newPlayer;
    }

    return $foundPlayers;
  }

  public function SearchPlayersByAssumedName($league, $firstName, $lastName) {
    $stmt = $this->pdo->prepare("SELECT * FROM pro_players WHERE league = :league AND (first_name LIKE :first_name AND last_name LIKE :last_name) LIMIT 15");
    $stmt->setFetchMode(\PDO::FETCH_CLASS, ProPlayer::class);
    $stmt->bindValue(':league', $league);
    $stmt->bindValue(':first_name', "%" . $firstName . "%");
    $stmt->bindValue(':last_name', "%" . $lastName . "%");

    $stmt->execute();

    $foundPlayers = array();

    while ($newPlayer = $stmt->fetch()) {
      $foundPlayers[] = $newPlayer;
    }

    return $foundPlayers;
  }

  // TODO(4.6): Nothing calls this until the admin CSV upload (ProPlayerService::Upload) is ported.
  /**
   * Delete existing players for a given league, upload new players
   * @param string $league
   * @param array $proPlayers Array of ProPlayer objects
   */
  public function SaveProPlayers($league, $proPlayers) {
    $delete_stmt = $this->pdo->prepare("DELETE FROM pro_players WHERE league = ?");
    $delete_stmt->bindParam(1, $league);

    if (!$delete_stmt->execute()) {
      throw new \Exception("Unable to empty existing pro players first.");
    }

    foreach ($proPlayers as $proPlayer) {
      $this->_saveProPlayer($proPlayer);
    }

    return;
  }

  /**
   * Adds a new pro player to the DB
   * @return ProPlayer the saved player, with its new ID.
   */
  private function _saveProPlayer(ProPlayer $proPlayer) {
    if ($proPlayer->pro_player_id > 0) {
      throw new \Exception("Unable to save pro player: invalid ID.");
    } else {
      $insertStmt = $this->pdo->prepare("INSERT INTO pro_players
        (pro_player_id, league, first_name, last_name, position, team)
        VALUES
        (NULL, ?, ?, ?, ?, ?)");

      $insertStmt->bindParam(1, $proPlayer->league);
      $insertStmt->bindParam(2, $proPlayer->first_name);
      $insertStmt->bindParam(3, $proPlayer->last_name);
      $insertStmt->bindParam(4, $proPlayer->position);
      $insertStmt->bindParam(5, $proPlayer->team);

      if (!$insertStmt->execute()) {
        throw new \Exception("Unable to save pro player.");
      }

      $proPlayer->pro_player_id = (int)$this->pdo->lastInsertId();

      return $proPlayer;
    }
  }
}
