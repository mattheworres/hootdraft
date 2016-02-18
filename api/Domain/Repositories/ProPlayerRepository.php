<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\ProPlayer;

class ProPlayerRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  /**
   * Search players for autocomplete feature on pro_players table
   * @param type $league Required - the league to search on
   * @param type $first Player first name search term
   * @param type $last Player last name search term
   * @param type $team Player team (abbreviation)
   * @param type $position Player position (abbreviation)
   * @return array
   */
  public function SearchPlayersManual($league, $first = "NA", $last = "NA", $team = "NA", $position = "NA") {
    //Approach taken from: http://stackoverflow.com/a/4540085/324527

    $searchSql = "SELECT * FROM pro_players WHERE league = :league";

    $searchParams = array();
    $regularParams = array();

    if ($first != "NA") {
      $searchParams['first_name'] = $first;
    }

    if ($last != "NA") {
      $searchParams['last_name'] = $last;
    }

    if ($team != "NA") {
      $regularParams['team'] = $team;
    }

    if ($position != "NA") {
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

    $stmt = $this->app['db']->prepare($searchSql);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\ProPlayer');
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

    return array_slice($foundPlayers, 0, 15);
  }

  public function SearchPlayers($league, $searchTerm) {
    //Limit the amount of searches to just 15 to cut down on unnecessary network traffic
    $stmt = $this->app['db']->prepare("SELECT * FROM pro_players WHERE league = :league AND (first_name LIKE :search_term OR last_name LIKE :search_term) LIMIT 15");
    $stmt->setFetchMode(\PDO::FETCH_CLASS, 'PhpDraft\Domain\Entities\ProPlayer');
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
    $stmt = $this->app['db']->prepare("SELECT * FROM pro_players WHERE league = :league AND (first_name LIKE :first_name AND last_name LIKE :last_name) LIMIT 15");
    $stmt->setFetchMode(\PDO::FETCH_CLASS, 'PhpDraft\Domain\Entities\ProPlayer');
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

  /**
   * Delete existing players for a given league, upload new players
   * @param array $players Array of pro_player_object's
   */
  public function SaveProPlayers($league, $proPlayers) {
    $delete_sql = "DELETE FROM pro_players WHERE league = '" . $league . "'";

    $delete_success = $this->app['db']->exec($delete_sql);

    if ($delete_success === false) {
      throw new \Exception("Unable to empty existing pro players first.");
    }

    foreach ($proPlayers as $proPlayer) {
      $this->_saveProPlayer($proPlayer);
    }

    return;
  }

   /**
   * Adds a new pro player to the DB
   * @return boolean success whether or not the database operation succeeded.
   */
  private function _saveProPlayer(ProPlayer $proPlayer) {
    if ($proPlayer->pro_player_id > 0) {
      throw new \Exception("Unable to save pro player: invalid ID.");
    } else {
      $insertStmt = $this->app['db']->prepare("INSERT INTO pro_players 
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

      $proPlayer->draft_id = (int) $this->app['db']->lastInsertId();

      return $proPlayer;
    }
  }
}