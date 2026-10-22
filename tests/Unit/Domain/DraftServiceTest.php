<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Services\DraftService;
use PHPUnit\Framework\TestCase;

class DraftServiceTest extends TestCase
{
  private DraftService $sut;
  private Draft $draft;

  protected function setUp(): void
  {
    $this->sut = new DraftService(new DraftDataRepository(), $this->createStub(DraftRepository::class));
    $this->draft = new Draft();
  }

  public function testDraftStatusDisplay(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertSame('Setting Up', $this->sut->GetDraftStatusDisplay($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertSame('In Progress', $this->sut->GetDraftStatusDisplay($this->draft));

    $this->draft->draft_status = 'complete';
    $this->assertSame('Completed', $this->sut->GetDraftStatusDisplay($this->draft));
  }

  public function testDraftSettingUp(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertTrue($this->sut->DraftSettingUp($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertFalse($this->sut->DraftSettingUp($this->draft));
  }

  public function testDraftInProgress(): void
  {
    $this->draft->draft_status = 'undrafted';
    $this->assertFalse($this->sut->DraftInProgress($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertTrue($this->sut->DraftInProgress($this->draft));
  }

  public function testDraftCompleted(): void
  {
    $this->draft->draft_status = 'complete';
    $this->assertTrue($this->sut->DraftComplete($this->draft));

    $this->draft->draft_status = 'in_progress';
    $this->assertFalse($this->sut->DraftComplete($this->draft));
  }

  public function testGetCurrentPickReadsTheLoadedDraft(): void
  {
    $draft = new Draft();
    $draft->draft_current_pick = '7';
    $repository = $this->createMock(DraftRepository::class);
    $repository->expects($this->once())->method('Load')->with(3)->willReturn($draft);

    $this->assertSame(7, (new DraftService(new DraftDataRepository(), $repository))->GetCurrentPick('3'));
  }
}
