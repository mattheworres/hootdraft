<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhPDraft\Domain\Models\DepthChartPositionCreateModel;

class DepthChartPositionValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsDraftSportValid($draft_sport) {
    $valid = true;
    $errors = array();
    $draft_sports = $this->app['phpdraft.DraftDataRepository']->GetSports();

    if(strlen($draft_sport) < 3 || strlen($draft_sport) > 4 || strlen($draft_sports[$draft_sport]) == 0) {
      $errors[] = "Draft sport is an invalid value.";
      $valid = false;
    }

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }

  public function AreDepthChartPositionsValid(DepthChartPositionCreateModel $depthChartPositionCreateModel) {
    $valid = true;
    $errors = array();

    if(count($depthChartPositionCreateModel->positions) !== count(array_unique($depthChartPositionCreateModel->positions))) {
      $valid = false;
      $errors[] = "One or more of the positions are not unique.";
    }

    $rounds = 0;

    foreach($depthChartPositionCreateModel->positions as $depthChartPosition) {
      $rounds += (int)$depthChartPosition->slots;
    }

    if($rounds == 0) {
      $valid = false;
      $errors[] = "The depth chart positions must specify at least 1 slot";
    }

    if($rounds > 30) {
      $valid = false;
      $errors[] = "The depth chart positions cannot specify more than 30 slots in total.";
    }

    if(count($depthChartPositionCreateModel->positions) > 30) {
      $valid = false;
      $errors[] = "Too many depth chart positions have been provided - 30 is the maximum.";
    }

    return $this->app['phpdraft.ResponseFactory']($valid, $errors);
  }
}