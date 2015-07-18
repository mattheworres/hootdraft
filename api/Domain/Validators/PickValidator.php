<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsPickValidForAdd(Draft $draft, Pick $pick) {
    $valid = true;
    $errors = array();
    $teams = $this->app['phpdraft.DraftDataRepository']->GetTeams($draft->draft_sport);
    $positions = $this->app['phpdraft.DraftDataRepository']->GetPositions($draft->draft_sport, $draft->nfl_extended);

    if(empty($pick->first_name)
      || empty($pick->last_name)
      || empty($pick->team)
      || empty($pick->position)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if($pick->draft_id != $draft->draft_id) {
      $errors[] = "Pick does not belong to draft #$draft->draft_id.";
      $valid = false;
    }

    if(strlen($pick->first_name) > 255) {
      $errors[] = "First name is above maximum length.";
      $valid = false;
    }

    if(strlen($pick->last_name) > 255) {
      $errors[] = "Last name is above maximum length.";
      $valid = false;
    }

    if($draft->draft_current_pick != $pick->player_pick) {
      $errors[] = "Pick #$pick->player_pick is not the current pick for draft #$draft->draft_id.";
      $valid = false;
    }

    if(!array_key_exists($pick->team, $teams)) {
      $errors[] = "Team $pick->team is an invalid value.";
      $valid = false;
    }

    if(!array_key_exists($pick->position, $positions)) {
      $errors[] = "Position $pick->position is an invalid value.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }

  public function IsPickValidForUpdate(Draft $draft, Pick $pick) {
    $valid = true;
    $errors = array();
    $teams = $this->app['phpdraft.DraftDataRepository']->GetTeams($draft->draft_sport);
    $positions = $this->app['phpdraft.DraftDataRepository']->GetPositions($draft->draft_sport, $draft->nfl_extended);

    if(empty($pick->first_name)
      || empty($pick->last_name)
      || empty($pick->team)
      || empty($pick->position)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if($pick->draft_id != $draft->draft_id) {
      $errors[] = "Pick does not belong to draft #$draft->draft_id.";
      $valid = false;
    }

    if(strlen($pick->first_name) > 255) {
      $errors[] = "First name is above maximum length.";
      $valid = false;
    }

    if(strlen($pick->last_name) > 255) {
      $errors[] = "Last name is above maximum length.";
      $valid = false;
    }

    if(!array_key_exists($pick->team, $teams)) {
      $errors[] = "Team $pick->team is an invalid value.";
      $valid = false;
    }

    if(!array_key_exists($pick->position, $positions)) {
      $errors[] = "Position $pick->position is an invalid value.";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}