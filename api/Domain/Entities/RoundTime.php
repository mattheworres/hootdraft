<?php
namespace PhpDraft\Domain\Entities;

class RoundTime {
  /** @var int $round_time_id The unique identifier for this round time */
  public $round_time_id;

  /** @var int $draft_id The unique identifier for the draft this round time belongs to */
  public $draft_id;

  /** @var bool $is_static_time Determines if this round time is for all rounds (static/true) or is specified per round (dynamic/false) */
  public $is_static_time;

  /** @var int $draft_round The round this round time is for. If $is_static_time is true, this should be null */
  public $draft_round;

  /** @var int $round_time_seconds Number of whole seconds this round time specifies */
  public $round_time_seconds;

  public function __construct() {
    //Leaving this here in case any init is needed on new round times
  }
}