<?php
namespace PhpDraft\Test;

use PHPUnit\Framework\TestCase;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Services\DraftService;
use PhpDraft\Domain\Repositories\DraftDataRepository;
use PhpDraft\Domain\Models\PhpDraftResponse;

class DraftServiceTest extends TestCase {
  function setUp() {
    $this->app = require dirname(__FILE__).'/../../../../api/config/_app.php';
    $draftDataRepository = new DraftDataRepository($this->app);
    $this->app['phpdraft.DraftDataRepository'] = $draftDataRepository;
    $this->sut = new DraftService($this->app);

    $this->draft = new Draft();

    $this->_setupMocks();

    //TODO: How to not re-create this simple wheel?
    $this->app['phpdraft.ResponseFactory'] = function() {
      return function($success, $errors) {
        return new PhpDraftResponse($success, $errors);
      };
    };
  }

  public function testGetDraftStats() {
    $this->draftStatsRepoStub->expects($this->once())
          ->method('LoadDraftStats')
          ->willReturn(true);

    $this->app['phpdraft.DraftStatsRepository'] = $this->draftStatsRepoStub;

    $result = $this->sut->GetDraftStats(1);

    $this->assertTrue($result->draft_statistics);
  }

  public function testDraftStatusDisplay() {
    $this->draft->draft_status = "undrafted";

    $result = $this->sut->GetDraftStatusDisplay($this->draft);

    $this->assertEquals("Setting Up", $result);

    $this->draft->draft_status = "in_progress";

    $result = $this->sut->GetDraftStatusDisplay($this->draft);

    $this->assertEquals("In Progress", $result);

    $this->draft->draft_status = "complete";

    $result = $this->sut->GetDraftStatusDisplay($this->draft);

    $this->assertEquals("Completed", $result);
  }

  public function testDraftSettingUp() {
    $this->draft->draft_status = "undrafted";

    $result = $this->sut->DraftSettingUp($this->draft);

    $this->assertTrue($result);

    $this->draft->draft_status = "in_progress";

    $result = $this->sut->DraftSettingUp($this->draft);

    $this->assertFalse($result);
  }

  public function testDraftInProgress() {
    $this->draft->draft_status = "undrafted";

    $result = $this->sut->DraftInProgress($this->draft);

    $this->assertFalse($result);

    $this->draft->draft_status = "in_progress";

    $result = $this->sut->DraftInProgress($this->draft);

    $this->assertTrue($result);
  }

  public function testDraftCompleted() {
    $this->draft->draft_status = "complete";

    $result = $this->sut->DraftComplete($this->draft);

    $this->assertTrue($result);

    $this->draft->draft_status = "in_progress";

    $result = $this->sut->DraftComplete($this->draft);

    $this->assertFalse($result);
  }

  private function _setupMocks() {
    $this->draftStatsRepoStub = $this->getMockBuilder('PhpDraft\Domain\Repositories\DraftStatsRepository')
      ->disableOriginalConstructor()
      ->getMock();
  }
}
