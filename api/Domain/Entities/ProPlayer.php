<?php
namespace PhpDraft\Domain\Entities;

class ProPlayer {
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

  public function __construct() {
    //Leaving this empty
  }
}