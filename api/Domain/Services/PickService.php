<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  /*A replacement for the "hasBeenSelected" from 1.x - does in-memory logic to determine if pick is a selection yet*/
  public function PickHasBeenSelected(Pick $pick) {
    $hasTime = is_null($pick->pick_time);
    $hasDuration = is_null($pick->pick_duration);
    $selected = (!$hasTime && !$hasDuration);

    return $selected ? 1 : 0;
  }
}