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
  public function SearchPlayers($league, $first = "NA", $last = "NA", $team = "NA", $position = "NA") {
    //Approach taken from: http://stackoverflow.com/a/4540085/324527

    $search_sql = "SELECT * FROM pro_players WHERE league = :league";

    $search_params = array();
    $regular_params = array();

    if ($first != "NA") {
      $search_params['first_name'] = $first;
    }

    if ($last != "NA") {
      $search_params['last_name'] = $last;
    }

    if ($team != "NA") {
      $regular_params['team'] = $team;
    }

    if ($position != "NA") {
      $regular_params['position'] = $position;
    }

    //Finish building PDO SQL with dynamic values:
    foreach ($search_params as $key => $value) {
      $search_sql .= sprintf(' AND %s LIKE :%s', $key, $key);
    }

    foreach ($regular_params as $key => $value) {
      $search_sql .= sprintf(' AND %s = :%s', $key, $key);
    }

    $stmt = $this->app['db']->prepare($search_sql);
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\ProPlayer');
    $stmt->bindValue(':league', $league);

    //Assign values to those parameters:
    foreach ($search_params as $key => $value) {
      $stmt->bindValue(':' . $key, "%" . $value . "%");
    }

    foreach ($regular_params as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    $stmt->execute();

    $found_players = array();

    while ($newPlayer = $stmt->fetch()) {
      $found_players[] = $newPlayer;
    }

    return $found_players;
  }

  /**
   * Delete existing players for a given league, upload new players
   * @param array $players Array of pro_player_object's
   */
  public function SaveProPlayers($league, $pro_players) {
    $delete_sql = "DELETE FROM pro_players WHERE league = '" . $league . "'";

    $delete_success = $this->app['db']->exec($delete_sql);

    if ($delete_success === false) {
      throw new \Exception("Unable to empty existing pro players first.");
    }

    foreach ($pro_players as $pro_player) {
      $this->_saveProPlayer($pro_player);
    }

    return;
  }

   /**
   * Adds a new pro player to the DB
   * @return boolean success whether or not the database operation succeeded.
   */
  private function _saveProPlayer(ProPlayer $pro_player) {
    if ($pro_player->pro_player_id > 0) {
      throw new \Exception("Unable to save pro player: invalid ID.");
    } else {
      $insert_stmt = $this->app['db']->prepare("INSERT INTO pro_players 
        (pro_player_id, league, first_name, last_name, position, team) 
        VALUES 
        (NULL, ?, ?, ?, ?, ?)");

      $insert_stmt->bindParam(1, $pro_player->league);
      $insert_stmt->bindParam(2, $pro_player->first_name);
      $insert_stmt->bindParam(3, $pro_player->last_name);
      $insert_stmt->bindParam(4, $pro_player->position);
      $insert_stmt->bindParam(5, $pro_player->team);

      if (!$insert_stmt->execute()) {
        throw new \Exception("Unable to save pro player.");
      }

      $pro_player->draft_id = (int) $this->app['db']->lastInsertId();

      return $pro_player;
    }
  }
}