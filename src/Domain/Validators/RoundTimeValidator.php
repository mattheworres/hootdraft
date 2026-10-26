<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeValidator {
  public function AreRoundTimesValid(RoundTimeCreateModel $model) {
    $valid = true;
    $errors = array();

    if ($model->isRoundTimesEnabled) {
      $roundTimeNumber = 0;
      foreach ($model->roundTimes as $roundTime) {
        $roundTimeNumber++;

        if (empty($roundTime->draft_id) ||
          empty($roundTime->round_time_seconds)) {
          $errors[] = "Round time #$roundTimeNumber has one or more missing fields.";
          $valid = false;
        }

        // PHP 8 no longer treats a non-numeric string as 0 in this comparison, so cast first
        if ((int) $roundTime->round_time_seconds <= 0) {
          $errors[] = "Round time #$roundTimeNumber must have 1 or more seconds specified.";
          $valid = false;
        }

        if (!$roundTime->is_static_time && ($roundTime->draft_round < 1 || $roundTime->draft_round > 30)) {
          $errors[] = "Round time #$roundTimeNumber cannot have a round less than 1 or greater than 30.";
          $valid = false;
        }
      }
    }

    return new HootDraftResponse($valid, $errors);
  }
}
