<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function SaveRoundTimes(Draft $draft, RoundTimeCreateModel $model) {
    $response = new PhpDraftResponse();

    try {
      $this->app['phpdraft.RoundTimeRepository']->DeleteAll($draft->draft_id);
      $roundTimes = $this->app['phpdraft.RoundTimeRepository']->Update($model->roundTimes);

      $response->success = true;
      $response->roundTimes = $roundTimes;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
}