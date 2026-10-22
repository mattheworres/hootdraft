<?php

namespace PhpDraft\Domain\Entities;

class DraftStats {
  public $draft_stat_id;
  public $draft_id;
  public $drafting_time_seconds;
  public $longest_avg_pick_manager_name;
  public $longest_avg_pick_seconds;
  public $shortest_avg_pick_manager_name;
  public $shortest_avg_pick_seconds;
  public $longest_single_pick_manager_name;
  public $longest_single_pick_seconds;
  public $shortest_single_pick_manager_name;
  public $shortest_single_pick_seconds;
  public $average_pick_seconds;
  public $longest_round;
  public $longest_round_seconds;
  public $shortest_round;
  public $shortest_round_seconds;
  public $average_round_seconds;
  public $most_drafted_team;
  public $most_drafted_team_count;
  public $least_drafted_team;
  public $least_drafted_team_count;
  public $most_drafted_position;
  public $most_drafted_position_count;
  public $least_drafted_position;
  public $least_drafted_position_count;
}