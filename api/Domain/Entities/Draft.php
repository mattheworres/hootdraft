<?php
namespace PhpDraft\Domain\Entities;

/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 */
class Draft {

  /** @var int $draft_id The unique identifier for this draft */
  public $draft_id;

  /** @var string $draft_name The string identifier for this draft */
  public $draft_name;

  /** @var string $draft_sport */
  public $draft_sport;

  /** @var string $draft_status The description of the status is in */
  public $draft_status;

  /** @var string $draft_style Either 'serpentine' or 'standard' */
  public $draft_style;

  /** @var int $draft_rounds Number of rounds draft will have in total */
  public $draft_rounds;
  
  /** @var int $draft_rounds Counter used to determine if a board is stale and needs updating. */
  public $draft_counter;

  /** @var string $draft_password Determines draft visibility */
  public $draft_password;

  /** @var string $draft_start_time datetime of when the draft was put into "started" status */
  public $draft_start_time;

  /** @var string $draft_end_time datetime of when the draft was put into "completed" status */
  public $draft_end_time;

  /** @var int $draft_current_round */
  public $draft_current_round;

  /** @var int $draft_current_pick */
  public $draft_current_pick;

  /** @var array $sports_teams An array of all of the teams in the pro sport. Capitalized abbreviation is key, full name is value. */
  public $sports_teams;

  /** @var array $sports_positions An array of all the positions in the pro sport. Capitalized abbreviation is key, full name is value. */
  public $sports_positions;

  /** @var array $sports_colors An array of all the colors used for each position in the draft. Capitalized position abbreviation is key, hex color string is value (with # prepended) */
  public $sports_colors;

  public function __construct() {
    //Leaving this here in case any init is needed on new drafts.
  }

}