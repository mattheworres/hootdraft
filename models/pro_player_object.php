<?php

/**
 * Represents a professional sports player, used for pick entry autocompletion.
 * 
 */
class pro_player_object {

  /** @var int */
  public $pro_player_id;

  /** @var string Three character abbreviation of league player belongs to. NFL, NHL, MLB, NBA possible values */
  public $league;

  /** @var string */
  public $first_name;

  /** @var string */
  public $last_name;

  /** @var string Abbreviation of the position the player plays */
  public $position;

  /** @var string Abbreviation of the city of the team the player plays for */
  public $team;

  public function __construct($id = 0) {
    global $DBH; /* @var $DBH PDO */
    $id = (int) $id;

    if ($id == 0)
      return false;

    $pro_player_stmt = $DBH->prepare("SELECT * FROM pro_players WHERE pro_player_id = ? LIMIT 1");
    $pro_player_stmt->setFetchMode(PDO::FETCH_INTO, $this);
    $pro_player_stmt->bindParam(1, $id);

    if (!$pro_player_stmt->execute())
      return false;

    if (!$pro_player_stmt->fetch())
      return false;

    return true;
  }

  /**
   * Adds a new pro player to the DB
   * @return boolean success whether or not the MySQL transaction succeeded.
   */
  public function savePlayer() {
    global $DBH; /* @var $DBH PDO */
    if ($this->pro_player_id > 0) {
      return false;
    } else {
      $insert_stmt = $DBH->prepare("INSERT INTO pro_players 
				(pro_player_id, league, first_name, last_name, position, team) 
				VALUES 
				(NULL, ?, ?, ?, ?, ?)");

      $insert_stmt->bindParam(1, $this->league);
      $insert_stmt->bindParam(2, $this->first_name);
      $insert_stmt->bindParam(3, $this->last_name);
      $insert_stmt->bindParam(4, $this->position);
      $insert_stmt->bindParam(5, $this->team);

      if (!$insert_stmt->execute())
        return false;

      $this->draft_id = (int) $DBH->lastInsertId();

      return true;
    }
  }

  /**
   * Delete existing players for a given league, upload new players
   * @param array $players Array of pro_player_object's
   */
  public static function SavePlayers($league, $players) {
    if ($league != "MLB" && $league != "NHL" && $league != "NFL" && $league != "NBA")
      return false;

    global $DBH; /* @var $DBH PDO */

    $delete_sql = "DELETE FROM pro_players WHERE league = '" . $league . "'";

    $delete_success = $DBH->exec($delete_sql);

    if ($delete_success === false)
      return false;

    foreach ($players as $player) {
      /* @var $player pro_player_object */
      if (!$player->savePlayer()) {
        return false;
      }
    }

    return true;
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
  public static function SearchPlayers($league, $first = "NA", $last = "NA", $team = "NA", $position = "NA") {
    global $DBH; /* @var $DBH PDO */
    //Approach taken from: http://stackoverflow.com/a/4540085/324527

    $search_sql = "SELECT * FROM pro_players WHERE league = :league";

    $search_params = array();
    $regular_params = array();

    if ($first != "NA")
      $search_params['first_name'] = $first;

    if ($last != "NA")
      $search_params['last_name'] = $last;

    if ($team != "NA")
      $regular_params['team'] = $team;

    if ($position != "NA")
      $regular_params['position'] = $position;


    //Finish building PDO SQL with dynamic values:
    foreach ($search_params as $key => $value) {
      $search_sql .= sprintf(' AND %s LIKE :%s', $key, $key);
    }

    foreach ($regular_params as $key => $value) {
      $search_sql .= sprintf(' AND %s = :%s', $key, $key);
    }

    $stmt = $DBH->prepare($search_sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, 'pro_player_object');
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

    while ($newPlayer = $stmt->fetch())
      $found_players[] = $newPlayer;

    return $found_players;
  }

}

?>
