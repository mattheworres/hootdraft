<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProPlayerValidator {
  public function __construct(private DraftDataRepository $draftData) {
  }

  public function IsUploadSportValid($sport, ?UploadedFile $file) {
    $valid = true;
    $errors = array();
    $sports = $this->draftData->GetSports();

    if (empty($sport)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if (!array_key_exists((string) $sport, $sports)) {
      $errors[] = "Sport $sport is an invalid value.";
      $valid = false;
    }

    if ($file === null) {
      $valid = false;
      $errors[] = "Must upload a CSV file";
    } else {
      $fileErrorCode = $file->getError();

      if ($fileErrorCode > 0) {
        $valid = false;
        $errors[] = "Upload error - " . $fileErrorCode;
      }
    }

    return new HootDraftResponse($valid, $errors);
  }
}
