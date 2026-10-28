<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Domain\Validators\DraftValidator;
use HootDraft\Security\CurrentUser;
use HootDraft\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DraftValidatorTest extends TestCase
{
  private const HEADER = 'X-Test-Password';

  private DraftRepository $repository;
  private CurrentUser $currentUser;
  private DraftValidator $sut;

  protected function setUp(): void
  {
    $data = new DraftDataRepository();
    $this->repository = $this->createStub(DraftRepository::class);
    $this->currentUser = $this->createStub(CurrentUser::class);
    $this->sut = $this->validator($this->repository, $data, $this->currentUser);
  }

  private function validator(DraftRepository $repository, DraftDataRepository $data, CurrentUser $currentUser, ?ManagerRepository $managers = null): DraftValidator
  {
    $managers ??= $this->createStub(ManagerRepository::class);
    $service = new DraftService($data, $repository, $this->createStub(DepthChartPositionRepository::class), $managers, $this->createStub(RoundTimeRepository::class), $this->createStub(PickRepository::class), $this->createStub(\PDO::class), $this->createStub(TradeRepository::class), $this->createStub(DraftStatsRepository::class));

    return new DraftValidator($repository, $data, $service, $managers, $currentUser, self::HEADER);
  }

  private function draft(array $props = []): Draft
  {
    $draft = new Draft();
    $draft->draft_id = 5;
    $draft->commish_id = 10;
    $draft->draft_name = 'Test draft';
    $draft->draft_sport = 'NFL';
    $draft->draft_style = 'serpentine';
    $draft->draft_rounds = 10;
    $draft->draft_status = 'undrafted';

    foreach ($props as $key => $value) {
      $draft->$key = $value;
    }

    return $draft;
  }

  private function user(int $id, array $roles = ['ROLE_COMMISH']): User
  {
    return new User($id, "u$id@example.com", "User $id", 'x', null, $roles, true);
  }

  private function viewable(Draft $draft, ?User $user, ?string $password = null): bool
  {
    // a stub keeps its first configuration, so each call gets fresh collaborators
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('Load')->willReturn($draft);
    $currentUser = $this->createStub(CurrentUser::class);
    $currentUser->method('get')->willReturn($user);
    $data = new DraftDataRepository();
    $sut = $this->validator($repository, $data, $currentUser);
    $request = new Request();
    if ($password !== null) {
      $request->headers->set(self::HEADER, $password);
    }

    return $sut->IsDraftViewableForUser(5, $request);
  }

  public function testPublicDraftIsViewableByAnonymous(): void
  {
    $this->assertTrue($this->viewable($this->draft(), null));
  }

  public function testPrivateDraftNeedsThePasswordHeaderOrOwnership(): void
  {
    $private = $this->draft(['draft_password' => 'secret']);

    $this->assertFalse($this->viewable($private, null));
    $this->assertFalse($this->viewable($private, null, 'wrong'));
    $this->assertTrue($this->viewable($private, null, 'secret'));
    $this->assertTrue($this->viewable($private, $this->user(10)));
    $this->assertFalse($this->viewable($private, $this->user(11)));
    $this->assertTrue($this->viewable($private, $this->user(11, ['ROLE_ADMIN'])));
  }

  public function testEditableByOwnerOrAdminOnly(): void
  {
    $draft = $this->draft();

    $this->assertTrue($this->sut->IsDraftEditableForUser($draft, $this->user(10)));
    $this->assertTrue($this->sut->IsDraftEditableForUser($draft, $this->user(11, ['ROLE_ADMIN'])));
    $this->assertFalse($this->sut->IsDraftEditableForUser($draft, $this->user(11)));
    $this->assertFalse($this->sut->IsDraftEditableForUser($draft, null));
  }

  public function testStateChecksReportTheCurrentStatus(): void
  {
    $draft = $this->draft(['draft_status' => 'complete']);

    $result = $this->sut->IsDraftInProgress($draft);

    $this->assertFalse($result->success);
    $this->assertSame(['Unable to work on draft #5: draft is completed'], $result->errors);
    $this->assertTrue($this->sut->IsDraftComplete($draft)->success);
    $this->assertTrue($this->sut->IsDraftInProgressOrComplete($draft)->success);
    $this->assertFalse($this->sut->IsDraftSettingUp($draft)->success);
    $this->assertFalse($this->sut->IsDraftSettingUpOrInProgress($draft)->success);
    $this->assertTrue($this->sut->IsDraftSettingUpOrInProgress($this->draft(['draft_status' => 'in_progress']))->success);
  }

  public function testGracePeriodAcceptsRecentlyCompletedAndInProgressDrafts(): void
  {
    $this->assertTrue($this->sut->IsDraftInProgressOrCompletedInLessThanTen($this->draft(['draft_status' => 'in_progress']))->success);
    $this->assertFalse($this->sut->IsDraftInProgressOrCompletedInLessThanTen($this->draft())->success);

    $recent = $this->draft(['draft_status' => 'complete', 'draft_end_time' => gmdate('Y-m-d H:i:s', time() - 60)]);
    $this->assertTrue($this->sut->IsDraftInProgressOrCompletedInLessThanTen($recent)->success);
  }

  public function testCreateAndUpdateValidation(): void
  {
    $this->repository->method('NameIsUnique')->willReturn(true);

    $this->assertTrue($this->sut->IsDraftValidForCreateAndUpdate($this->draft())->success);

    $bad = $this->draft(['draft_name' => '', 'draft_sport' => 'XYZ', 'draft_style' => 'weird', 'draft_rounds' => 31]);
    $result = $this->sut->IsDraftValidForCreateAndUpdate($bad);

    $this->assertFalse($result->success);
    $this->assertSame([
      'One or more missing fields.',
      'Draft sport is an invalid value.',
      'Draft style is an invalid value.',
      'Invalid number of draft rounds.',
    ], $result->errors);
  }

  public function testCreateAndUpdateRejectsATakenName(): void
  {
    $this->repository->method('NameIsUnique')->willReturn(false);

    $result = $this->sut->IsDraftValidForCreateAndUpdate($this->draft());

    $this->assertSame(['Draft name is already taken, please choose a different name.'], $result->errors);
  }

  public function testStatusChangeNeedsAKnownStatusThatIsNotComplete(): void
  {
    $this->assertTrue($this->sut->IsDraftStatusValid($this->draft(['draft_status' => 'undrafted']), 'in_progress')->success);

    $result = $this->sut->IsDraftStatusValid($this->draft(['draft_status' => 'bogus']), 'undrafted');
    $this->assertContains('Draft status is an invalid value.', $result->errors);

    $result = $this->sut->IsDraftStatusValid($this->draft(['draft_status' => '']), 'undrafted');
    $this->assertContains('One or more missing fields.', $result->errors);

    $result = $this->sut->IsDraftStatusValid($this->draft(['draft_status' => 'complete']), 'in_progress');
    $this->assertContains('You cannot set the draft as completed manually.', $result->errors);

    $result = $this->sut->IsDraftStatusValid($this->draft(['draft_status' => 'undrafted']), 'complete');
    $this->assertContains('The draft is completed, therefore its status cannot be changed.', $result->errors);
  }

  public function testAnInProgressDraftNeedsAtLeastTwoManagers(): void
  {
    $managers = $this->createStub(ManagerRepository::class);
    $managers->method('DraftHasManagers')->willReturn(false);
    $sut = $this->validator($this->repository, new DraftDataRepository(), $this->currentUser, $managers);

    $result = $sut->IsDraftStatusValid($this->draft(['draft_status' => 'in_progress']), 'undrafted');

    $this->assertFalse($result->success);
    $this->assertContains('A draft must have at least 2 managers before it can begin.', $result->errors);
  }
}
