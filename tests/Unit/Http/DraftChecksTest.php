<?php

namespace HootDraft\Tests\Unit\Http;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Validators\DraftValidator;
use HootDraft\Http\Check\CommishEditableDraftCheck;
use HootDraft\Http\Check\DraftInProgressCheck;
use HootDraft\Http\Check\DraftInProgressOrCompletedCheck;
use HootDraft\Http\Check\DraftInProgressOrCompletedTenMinutesCheck;
use HootDraft\Http\Check\DraftSettingUpOrInProgressCheck;
use HootDraft\Http\Check\DraftViewableCheck;
use HootDraft\Security\CurrentUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DraftChecksTest extends TestCase
{
  private function request(): Request
  {
    return new Request([], [], ['draft_id' => '5']);
  }

  public function testAliasesMatchTheNamesRoutesUse(): void
  {
    $this->assertSame('draftViewable', DraftViewableCheck::alias());
    $this->assertSame('draftSettingUp', DraftSettingUpOrInProgressCheck::alias());
    $this->assertSame('draftInProgress', DraftInProgressCheck::alias());
    $this->assertSame('draftInProgressOrCompleted', DraftInProgressOrCompletedCheck::alias());
    $this->assertSame('draftInProgressOrCompletedTenMinutes', DraftInProgressOrCompletedTenMinutesCheck::alias());
    $this->assertSame('commishEditableDraft', CommishEditableDraftCheck::alias());
  }

  public function testViewableCheck(): void
  {
    $validator = $this->createStub(DraftValidator::class);
    $validator->method('IsDraftViewableForUser')->willReturnOnConsecutiveCalls(true, false);
    $sut = new DraftViewableCheck($validator);

    $this->assertNull($sut->check($this->request()));

    $response = $sut->check($this->request());
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame('[{"success":false,"errors":["Draft marked as private."]}]', $response->getContent());
  }

  /** @return iterable<string, array{class-string, string}> */
  public static function stateChecks(): iterable
  {
    yield 'setting up' => [DraftSettingUpOrInProgressCheck::class, 'IsDraftSettingUpOrInProgress'];
    yield 'in progress' => [DraftInProgressCheck::class, 'IsDraftInProgress'];
    yield 'in progress or completed' => [DraftInProgressOrCompletedCheck::class, 'IsDraftInProgressOrComplete'];
    yield 'ten minutes' => [DraftInProgressOrCompletedTenMinutesCheck::class, 'IsDraftInProgressOrCompletedInLessThanTen'];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('stateChecks')]
  public function testStateChecksPassOrWrapTheValidatorFailure(string $class, string $validatorMethod): void
  {
    $draft = new Draft();
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Load')->willReturn($draft);
    $validator = $this->createStub(DraftValidator::class);
    $validator->method($validatorMethod)->willReturnOnConsecutiveCalls(new HootDraftResponse(true), new HootDraftResponse(false, ['nope']));
    $sut = new $class($repository, $validator);

    $this->assertNull($sut->check($this->request()));

    $response = $sut->check($this->request());
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame('[{"success":false,"errors":["nope"]}]', $response->getContent());
  }

  public function testCommishEditableCheck(): void
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Load')->willReturn(new Draft());
    $currentUser = $this->createStub(CurrentUser::class);
    $validator = $this->createStub(DraftValidator::class);
    $validator->method('IsDraftEditableForUser')->willReturnOnConsecutiveCalls(true, false);
    $sut = new CommishEditableDraftCheck($repository, $validator, $currentUser);

    $this->assertNull($sut->check($this->request()));

    $response = $sut->check($this->request());
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame('[{"success":false,"errors":["You do not have permission to this draft."]}]', $response->getContent());
  }
}
