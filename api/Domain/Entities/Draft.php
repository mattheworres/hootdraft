<?php
namespace PhpDraft\Domain\Entities;

/**
 * Represents a PHPDraft "draft" object, which is the parent object.
 *
 * A draft has many managers, and managers have many players (picks).
 * @property bool $draft_visible True if the draft is publicly visible, false otherwise
 * @property string $commish_name Name of the owning commissioner. Comes as an outer join with users.
 * @property string $status_display A string that can be used in display of the draft's status
 * @property array $sports_teams An array of all of the teams in the pro sport. Capitalized abbreviation is key, full name is value.
 * @property array $sports_positions An array of all the positions in the pro sport. Capitalized abbreviation is key, full name is value.
 * @property array $sports_colors An array of all the colors used for each position in the draft. Capitalized position abbreviation is key, hex color string is value (with # prepended)
 * @property array $sports Array of all possible values used for $draft_sport
 * @property array $styles Array of all possible values used for $draft_style
 * @property array $statuses Array of all possible values used for $draft_status
 */
class Draft {
  public function __construct() {
    //Leaving this here in case any init is needed on new drafts.
  }

  /** @var int $draft_id The unique identifier for this draft */
  public $draft_id;

  /** @var int $manager_id The unique identifier for the commissioner that owns this draft */
  public $commish_id;

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

  /** @var string $draft_end_time datetime of when the draft was put into "complete" status */
  public $draft_end_time;

  /** @var int $draft_current_round */
  public $draft_current_round;

  /** @var int $draft_current_pick */
  public $draft_current_pick;

  /** @var bool $nfl_extended Whether or not this draft uses extended NFL positions */
  public $nfl_extended;
}