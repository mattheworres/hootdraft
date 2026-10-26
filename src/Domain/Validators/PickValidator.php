<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Pick;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;

class PickValidator {
  public function __construct(
    private DraftDataRepository $draftData,
    private DepthChartPositionRepository $depthChartPositions,
  ) {
  }

  public function IsPickValidForAdd(Draft $draft, Pick $pick) {
    $valid = true;
    $errors = array();
    $teams = $this->draftData->GetTeams($draft->draft_sport);
    $positions = $this->draftData->GetPositions($draft->draft_sport);

    if (empty($pick->first_name)
      || empty($pick->last_name)
      || empty($pick->team)
      || empty($pick->position)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if ($pick->draft_id != $draft->draft_id) {
      $errors[] = "Pick does not belong to draft #$draft->draft_id.";
      $valid = false;
    }

    if (strlen((string) $pick->first_name) > 255) {
      $errors[] = "First name is above maximum length.";
      $valid = false;
    }

    if (strlen((string) $pick->last_name) > 255) {
      $errors[] = "Last name is above maximum length.";
      $valid = false;
    }

    if ($draft->draft_current_pick != $pick->player_pick) {
      $errors[] = "Pick #$pick->player_pick is not the current pick for draft #$draft->draft_id.";
      $valid = false;
    }

    if (!array_key_exists((string) $pick->team, $teams)) {
      $errors[] = "Team $pick->team is an invalid value.";
      $valid = false;
    }

    if (!array_key_exists((string) $pick->position, $positions)) {
      $errors[] = "Position $pick->position is an invalid value.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsPickValidForUpdate(Draft $draft, Pick $pick) {
    $valid = true;
    $errors = array();
    $teams = $this->draftData->GetTeams($draft->draft_sport);
    $positions = $this->draftData->GetPositions($draft->draft_sport);

    if (empty($pick->first_name)
      || empty($pick->last_name)
      || empty($pick->team)
      || empty($pick->position)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if ($pick->draft_id != $draft->draft_id) {
      $errors[] = "Pick does not belong to draft #$draft->draft_id.";
      $valid = false;
    }

    if (strlen((string) $pick->first_name) > 255) {
      $errors[] = "First name is above maximum length.";
      $valid = false;
    }

    if (strlen((string) $pick->last_name) > 255) {
      $errors[] = "Last name is above maximum length.";
      $valid = false;
    }

    if (!array_key_exists((string) $pick->team, $teams)) {
      $errors[] = "Team $pick->team is an invalid value.";
      $valid = false;
    }

    if (!array_key_exists((string) $pick->position, $positions)) {
      $errors[] = "Position $pick->position is an invalid value.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsPickValidForDepthChartUpdate(Draft $draft, Pick $pick) {
    $valid = true;
    $errors = array();

    if ($pick->draft_id != $draft->draft_id) {
      $errors[] = "Pick does not belong to draft #$draft->draft_id.";
      $valid = false;
    }

    if (!$draft->using_depth_charts) {
      $errors[] = "Draft #$draft->draft_id does not use depth charts.";
      $valid = false;
    } else if ($pick->depth_chart_position_id !== null) {
      // A null position is the "Unassigned" bucket; anything else has to be one of this draft's positions
      $positionIds = array_map(fn($position) => (int) $position->id, $this->depthChartPositions->LoadAll($draft->draft_id));

      if (!in_array((int) $pick->depth_chart_position_id, $positionIds, true)) {
        $errors[] = "Depth chart position #$pick->depth_chart_position_id does not belong to draft #$draft->draft_id.";
        $valid = false;
      }
    }

    return new HootDraftResponse($valid, $errors);
  }
}
