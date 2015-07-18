<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class ProPlayerValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsUploadSportValid($sport, &$file) {
    $valid = true;
    $errors = array();
    $sports = $this->app['phpdraft.DraftDataRepository']->GetSports();

    if(empty($sport)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if(!array_key_exists($sport, $sports)) {
      $errors[] = "Sport $sport is an invalid value.";
      $valid = false;
    }

    if (!isset($file)) {
      $valid = false;
      $errors[] =  "Must upload a CSV file";
    }

    if ($file->getError() > 0) {
      $valid = false;
      $errors[] = "Upload error - " . $file->getError();
    }

    $extension = $file->getExtension();

    if ($extension != "csv") {
      $valid = false;
      $errors[] = "File uploaded must be a CSV!";
    }

    return new PhpDraftResponse($valid, $errors);
  }
}