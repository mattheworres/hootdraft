<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function AreRoundTimesValid(RoundTimeCreateModel $model) {
    $valid = true;
    $errors = array();

    if(empty($model->isRoundTimesEnabled)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if($model->isRoundTimesEnabled) {
      $roundTimeNumber = 0;
      foreach($model->roundTimes as $roundTime) {
        $roundTimeNumber++;

        if(empty($roundTime->draft_id) ||
          empty($roundTime->is_static_time) ||
          empty($roundTime->round_time_seconds)) {
          $errors[] = "Round time #$roundTimeNumber has one or more missing fields.";
          $valid = false;
        }

        if($roundTime->round_time_seconds <= 0) {
          $errors[] = "Round time #$roundTimeNumber must have 1 or more seconds specified.";
        }
      }
    }

    return new PhpDraftResponse($valid, $errors);
  }
}