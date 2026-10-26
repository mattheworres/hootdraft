<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\RoundTime;
use HootDraft\Domain\Models\RoundTimeCreateModel;
use HootDraft\Domain\Validators\RoundTimeValidator;
use PHPUnit\Framework\TestCase;

class RoundTimeValidatorTest extends TestCase
{
  private RoundTimeCreateModel $roundTimeCreateModel;
  private RoundTimeValidator $sut;

  protected function setUp(): void
  {
    $this->roundTimeCreateModel = new RoundTimeCreateModel();
    $this->sut = new RoundTimeValidator();
  }

  private function roundTime(?int $draftId, mixed $seconds, int $round): RoundTime
  {
    $roundTime = new RoundTime();
    $roundTime->draft_id = $draftId;
    $roundTime->round_time_seconds = $seconds;
    $roundTime->is_static_time = false;
    $roundTime->draft_round = $round;

    return $roundTime;
  }

  public function testPassesValidationIfTimersAreDisabled(): void
  {
    $this->roundTimeCreateModel->isRoundTimesEnabled = false;

    $this->assertTrue($this->sut->AreRoundTimesValid($this->roundTimeCreateModel)->success);
  }

  public function testPassesValidTimers(): void
  {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, 90, 1);

    $static = $this->roundTime(1, 60, 0);
    $static->is_static_time = true;
    $static->draft_round = null;
    $this->roundTimeCreateModel->roundTimes[] = $static;

    $this->assertTrue($this->sut->AreRoundTimesValid($this->roundTimeCreateModel)->success);
  }

  public function testDraftIdAndSecondsArePresent(): void
  {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, null, 1);
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(null, 7, 1);

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #2 has one or more missing fields.", $result->errors);
    $this->assertContains("Round time #1 has one or more missing fields.", $result->errors);
  }

  public function testRoundTimeSecondsArePositiveNumbers(): void
  {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, -5, 1);
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, "q", 1);

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #1 must have 1 or more seconds specified.", $result->errors);
    $this->assertContains("Round time #2 must have 1 or more seconds specified.", $result->errors);
  }

  public function testEnsureRoundNumberIsCorrect(): void
  {
    $this->roundTimeCreateModel->isRoundTimesEnabled = true;
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, 5, 0);
    $this->roundTimeCreateModel->roundTimes[] = $this->roundTime(1, 5, 31);

    $result = $this->sut->AreRoundTimesValid($this->roundTimeCreateModel);

    $this->assertFalse($result->success);
    $this->assertContains("Round time #1 cannot have a round less than 1 or greater than 30.", $result->errors);
    $this->assertContains("Round time #2 cannot have a round less than 1 or greater than 30.", $result->errors);
  }
}
