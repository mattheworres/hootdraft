<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use \PhpDraft\Domain\Entities\RoundTime;
use PhpDraft\Domain\Validators\RoundTimeValidator;
use PhpDraft\Domain\Models\RoundTimeCreateModel;

class RoundTimeValidatorTest extends TestCase {
  function setUp() {
    $this->app = require dirname(__FILE__).'/../../../../api/config/_app.php';
    $this->roundTimeCreateModel = new RoundTimeCreateModel();
    $this->sut = new RoundTimeValidator($this->app);
  }

  public function testPassesValidationIfTimersAreDisabled() {
    $this->roundTimeCreateModel->isRoundTimesEnabled = false;

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertTrue($result->success);
  }

  public function testDraftIdAndSecondsArePresent() {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;

    $roundTime1 = new RoundTime();
    $roundTime1->draft_id = 1;
    $roundTime1->is_static_time = false;
    $roundTime1->draft_round = 1;

    $roundTime2 = new RoundTime();
    $roundTime2->round_time_seconds = 7;
    $roundTime2->is_static_time = false;
    $roundTime2->draft_round = 1;

    $this->roundTimeCreateModel->roundTimes[] = $roundTime1;
    $this->roundTimeCreateModel->roundTimes[] = $roundTime2;

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #2 has one or more missing fields.", $result->errors);
    $this->assertContains("Round time #1 has one or more missing fields.", $result->errors);
  }

  public function testRoundTimeSecondsArePositiveNumbers() {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;

    $roundTime1 = new RoundTime();
    $roundTime1->draft_id = 1;
    $roundTime1->round_time_seconds = -5;
    $roundTime1->is_static_time = false;
    $roundTime1->draft_round = 1;

    $roundTime2 = new RoundTime();
    $roundTime2->draft_id = 1;
    $roundTime2->round_time_seconds = "q";
    $roundTime2->is_static_time = false;
    $roundTime2->draft_round = 1;

    $this->roundTimeCreateModel->roundTimes[] = $roundTime1;
    $this->roundTimeCreateModel->roundTimes[] = $roundTime2;

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #1 must have 1 or more seconds specified.", $result->errors);
    $this->assertContains("Round time #2 must have 1 or more seconds specified.", $result->errors);
  }

  public function testEnsureRoundNumberIsCorrect() {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;

    $roundTime1 = new RoundTime();
    $roundTime1->draft_id = 1;
    $roundTime1->round_time_seconds = -5;
    $roundTime1->is_static_time = false;
    $roundTime1->draft_round = 0;

    $roundTime2 = new RoundTime();
    $roundTime2->draft_id = 1;
    $roundTime2->round_time_seconds = "q";
    $roundTime2->is_static_time = false;
    $roundTime2->draft_round = 31;

    $this->roundTimeCreateModel->roundTimes[] = $roundTime1;
    $this->roundTimeCreateModel->roundTimes[] = $roundTime2;

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #1 cannot have a round less than 1 or greater than 30.", $result->errors);
    $this->assertContains("Round time #2 cannot have a round less than 1 or greater than 30.", $result->errors);
  }
}