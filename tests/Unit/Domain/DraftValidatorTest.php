<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\DepthChartPosition;
use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Domain\Services\TransactionService;
use HootDraft\Domain\Validators\DepthChartPositionValidator;
use HootDraft\Domain\Validators\DraftValidator;
use HootDraft\Security\CurrentUser;
use HootDraft\Security\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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

  private function validator(DraftRepository $repository, DraftDataRepository $data, CurrentUser $currentUser, ?ManagerRepository $managers = null, ?DepthChartPositionRepository $positions = null): DraftValidator
  {
    $managers ??= $this->createStub(ManagerRepository::class);
    $positions ??= $this->createStub(DepthChartPositionRepository::class);
    $service = new DraftService($data, $repository, $this->createStub(DepthChartPositionRepository::class), $managers, $this->createStub(RoundTimeRepository::class), $this->createStub(PickRepository::class), $this->createStub(TransactionService::class), $this->createStub(TradeRepository::class), $this->createStub(DraftStatsRepository::class), $this->createStub(LoggerInterface::class));

    return new DraftValidator($repository, $data, $service, $managers, $positions, new DepthChartPositionValidator($data), $currentUser, self::HEADER);
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

  private function user(int $id, array $roles = [LoginUser::ROLE_COMMISH]): User
  {
    return new User($id, "u$id@example.com", "User $id", 'x', null, $roles, true, true);
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
    $this->assertTrue($this->viewable($private, $this->user(11, [LoginUser::ROLE_ADMIN])));
  }

  public function testEditableByOwnerOrAdminOnly(): void
  {
    $draft = $this->draft();

    $this->assertTrue($this->sut->IsDraftEditableForUser($draft, $this->user(10)));
    $this->assertTrue($this->sut->IsDraftEditableForUser($draft, $this->user(11, [LoginUser::ROLE_ADMIN])));
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

  /** @param array<array{string, int}> $positions */
  private function positions(array $positions): DepthChartPositionCreateModel
  {
    $model = new DepthChartPositionCreateModel();
    $model->depthChartEnabled = true;

    foreach ($positions as $order => [$name, $slots]) {
      $position = new DepthChartPosition();
      $position->position = $name;
      $position->slots = $slots;
      $position->display_order = $order;
      $model->positions[] = $position;
    }

    return $model;
  }

  private function validatorWithStoredPositions(array $stored): DraftValidator
  {
    $repository = $this->createStub(DraftRepository::class);
    $repository->method('NameIsUnique')->willReturn(true);
    $positions = $this->createStub(DepthChartPositionRepository::class);
    $positions->method('LoadAll')->willReturn($this->positions($stored)->positions);

    return $this->validator($repository, new DraftDataRepository(), $this->currentUser, null, $positions);
  }

  public function testCreateChecksTheFieldsAndThenThePositions(): void
  {
    $this->repository->method('NameIsUnique')->willReturn(true);

    $this->assertTrue($this->sut->IsDraftValidForCreate($this->draft(), null)->success);
    $this->assertTrue($this->sut->IsDraftValidForCreate($this->draft(['using_depth_charts' => 1]), $this->positions([['QB', 1]]))->success);
    $this->assertSame(['The depth chart positions must specify at least 1 slot'], $this->sut->IsDraftValidForCreate($this->draft(['using_depth_charts' => 1]), $this->positions([['QB', 0]]))->errors);
    $this->assertSame(['One or more missing fields.'], $this->sut->IsDraftValidForCreate($this->draft(['draft_name' => '', 'using_depth_charts' => 1]), $this->positions([['QB', 0]]))->errors);
  }

  public function testUpdateWhileSettingUpChecksThePositions(): void
  {
    $sut = $this->validatorWithStoredPositions([['QB', 1]]);
    $draft = $this->draft(['using_depth_charts' => 1]);

    $this->assertTrue($sut->IsDraftValidForUpdate($draft, $this->positions([['WR', 2]]), false)->success);
    $this->assertFalse($sut->IsDraftValidForUpdate($draft, $this->positions([['WR', 0]]), true)->success);
    $this->assertTrue($sut->IsDraftValidForUpdate($this->draft(['using_depth_charts' => 0]), null, true)->success);
  }

  public function testUpdateAfterSettingUpRefusesADepthChartChange(): void
  {
    $sut = $this->validatorWithStoredPositions([['QB', 1], ['RB', 2]]);
    $inProgress = ['draft_status' => 'in_progress'];
    $refusal = ['Depth chart settings can only change while the draft is setting up.'];

    $this->assertTrue($sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 1]), $this->positions([['QB', 1], ['RB', 2]]), true)->success);
    $this->assertTrue($sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 0]), null, false)->success);

    $this->assertSame($refusal, $sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 0]), null, true)->errors);
    $this->assertSame($refusal, $sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 1]), $this->positions([['QB', 1]]), false)->errors);
    $this->assertSame($refusal, $sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 1]), $this->positions([['RB', 2], ['QB', 1]]), true)->errors);
    $this->assertSame($refusal, $sut->IsDraftValidForUpdate($this->draft($inProgress + ['using_depth_charts' => 1]), $this->positions([['QB', 1], ['RB', 3]]), true)->errors);
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
