<?php
namespace PhpDraft\Domain\Entities;

//Previously known as "Player_Object" - renamed "Pick" to reduce my own confusion
/**
* @property string $manager_name Name of the manager that made the pick. NOTE: Only available on selected picks, and is kind've a cheat.
* @property int $search_score
* @property bool $selected If this pick has been selected yet - driven from if $pick_time is null or not
*/
class Pick {
  public function __construct() {
    //Leaving this here in case further init needs to occur
  }

  /** @var int */
  public $player_id;

  /** @var int The ID of the manager this player belongs to */
  public $manager_id;

  /** @var int The ID of the draft this player belongs to */
  public $draft_id;

  /** @var string */
  public $first_name;

  /** @var string */
  public $last_name;

  /** @var string The professional team the player plays for. Stored as three character abbreviation */
  public $team;

  /** @var string The position the player plays. Stored as one to three character abbreviation. */
  public $position;
  
  /** @var int The counter value indicating in what order this pick was edited in relation to the entire draft */
  public $player_counter;

  /** @var string Timestamp of when the player was picked. Use strtotime to convert for comparisons, NULL for undrafted */
  public $pick_time;

  /** @var int Amount of seconds that were consumed during this pick since previous pick */
  public $pick_duration;

  /** @var int Round the player was selected in */
  public $player_round;

  /** @var int Pick the player was selected at */
  public $player_pick;
}