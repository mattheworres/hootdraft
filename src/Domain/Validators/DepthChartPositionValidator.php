<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;

class DepthChartPositionValidator {
  public function __construct(private DraftDataRepository $draftData) {
  }

  public function IsDraftSportValid($draft_sport) {
    $valid = true;
    $errors = array();
    $draft_sports = $this->draftData->GetSports();
    $draft_sport = (string) $draft_sport;

    if (strlen($draft_sport) < 3 || strlen($draft_sport) > 4 || strlen($draft_sports[$draft_sport] ?? '') == 0) {
      $errors[] = "Draft sport is an invalid value.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function AreDepthChartPositionsValid(DepthChartPositionCreateModel $depthChartPositionCreateModel) {
    $valid = true;
    $errors = array();

    if (count($depthChartPositionCreateModel->positions) !== count(array_unique($depthChartPositionCreateModel->positions))) {
      $valid = false;
      $errors[] = "One or more of the positions are not unique.";
    }

    $rounds = 0;
    $position_number = 1;

    // display_order isn't checked: the controller assigns it from the order the positions were given in
    foreach ($depthChartPositionCreateModel->positions as $depthChartPosition) {
      if (trim((string) $depthChartPosition->position) === '') {
        $valid = false;
        $errors[] = "Depth chart position #$position_number is missing its position.";
      }

      $slots = filter_var($depthChartPosition->slots, FILTER_VALIDATE_INT);

      if ($slots === false || $slots < 0) {
        $valid = false;
        $errors[] = "Depth chart position #$position_number must have a whole number of slots, zero or more.";
      } else {
        $rounds += $slots;
      }

      $position_number++;
    }

    if ($rounds == 0) {
      $valid = false;
      $errors[] = "The depth chart positions must specify at least 1 slot";
    }

    if ($rounds > 30) {
      $valid = false;
      $errors[] = "The depth chart positions cannot specify more than 30 slots in total.";
    }

    if (count($depthChartPositionCreateModel->positions) > 30) {
      $valid = false;
      $errors[] = "Too many depth chart positions have been provided - 30 is the maximum.";
    }

    return new HootDraftResponse($valid, $errors);
  }
}
