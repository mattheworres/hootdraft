<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Validators\PickValidator;
use PhpDraft\Domain\Repositories\DraftDataRepository;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickValidatorTest extends TestCase {
  function setUp() {
    $this->app = require __DIR__.'/../../../../api/config/_app.php';
    $draftDataRepository = new DraftDataRepository($this->app);
    $this->app['phpdraft.DraftDataRepository'] = $draftDataRepository;
    $this->sut = new PickValidator($this->app);

    $this->draft = new Draft();
    $this->draft->draft_sport = "NFL";

    $this->pick = new Pick();
    $this->pick->first_name = "Antonio";
    $this->pick->last_name = "Brown";
    $this->pick->team = "PIT";
    $this->pick->position = "WR";

    //TODO: How to not re-create this simple wheel?
    $this->app['phpdraft.ResponseFactory'] = function() {
      return function($success, $errors) {
        return new PhpDraftResponse($success, $errors);
      };
    };
  }

  public function testEnsuresFieldsArentEmptyOnAdd() {
    $this->pick->first_name = "";

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);

    $this->pick->first_name = "Antonio";
    $this->pick->last_name = "";

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);

    $this->pick->last_name = "Brown";
    $this->pick->team = "";

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);

    $this->pick->team = "PIT";
    $this->pick->position = "";

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("One or more missing fields.", $result->errors);
  }

  public function testDraftIdMatchesOnAdd() {
    $this->draft->draft_id = 1;
    $this->pick->draft_id = 2;

    $result = $this->sut->IsPickValidForAdd($this->draft, $this->pick);

    $this->assertFalse($result->success);
    $this->assertContains("Pick does not belong to draft #1.", $result->errors);
  }
}