<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsManagerValidForAssetRetrieval(Draft $draft, Manager $manager) {
    $valid = true;
    $errors = array();

    if($draft->draft_id != $manager->draft_id) {
      $errors[] = "Manager does not belong to draft #$draft->draft_id";
      $valid = false;
    }

    return new PhpDraftResponse($valid, $errors);
  }
}