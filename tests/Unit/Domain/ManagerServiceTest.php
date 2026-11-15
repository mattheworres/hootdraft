<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Services\ManagerService;
use HootDraft\Tests\Support\FakesPdoTransactions;
use PHPUnit\Framework\TestCase;

class ManagerServiceTest extends TestCase
{
  use FakesPdoTransactions;

  private function manager(int $id = 0, int $draftId = 5): Manager
  {
    $manager = new Manager();
    $manager->manager_id = $id;
    $manager->draft_id = $draftId;
    $manager->manager_name = "Manager $id";

    return $manager;
  }

  public function testCreateChecksTheLimitAgainUnderTheDraftLock(): void
  {
    // The validator saw 19 managers, but another request got in first
    $repository = $this->createMock(ManagerRepository::class);
    $repository->expects($this->once())->method('LockDraft')->with(5);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(20);
    $repository->expects($this->never())->method('Create');

    $response = (new ManagerService($repository, $this->transactions(0, 1)))->CreateNewManager($this->manager());

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to add more managers - 20 is the maximum number of managers allowed.'], $response->errors);
  }

  public function testCreateCommitsWhenThereIsRoom(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(19);
    $repository->method('Create')->willReturnArgument(0);

    $response = (new ManagerService($repository, $this->transactions(1, 0)))->CreateNewManager($this->manager(9));

    $this->assertTrue($response->success);
    $this->assertSame(9, $response->manager->manager_id);
  }

  public function testCreateManyChecksTheLimitAgainUnderTheDraftLock(): void
  {
    $repository = $this->createMock(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(19);
    $repository->expects($this->never())->method('CreateMany');

    $response = (new ManagerService($repository, $this->transactions(0, 1)))->CreateManyManagers(5, [$this->manager(1), $this->manager(2)]);

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to add 2 new managers - the maximum number of managers is 20. Draft currently has 19.'], $response->errors);
  }

  public function testCreateManyRollsBackWhenALaterInsertFails(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(0);
    $repository->method('CreateMany')->willThrowException(new \Exception('Unable to save managers for 5'));

    $response = (new ManagerService($repository, $this->transactions(0, 1)))->CreateManyManagers(5, [$this->manager(1), $this->manager(2)]);

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to save managers for 5'], $response->errors);
  }

  public function testReorderRollsBackWhenAnUpdateFails(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('ReorderManagers')->willThrowException(new \Exception('Unable to update manager order for manager #4'));

    $response = (new ManagerService($repository, $this->transactions(0, 1)))->ReorderManagers([3, 4]);

    $this->assertFalse($response->success);
  }

  public function testDeleteRenumbersTheRemainingManagersInTheSameTransaction(): void
  {
    $repository = $this->createMock(ManagerRepository::class);
    $repository->expects($this->once())->method('DeleteManager')->with(2);
    $repository->expects($this->once())->method('ReorderManagers')->with([1, 3]);
    $repository->method('GetManagersByDraftOrder')->willReturnOnConsecutiveCalls(
      [$this->manager(1), $this->manager(3)],
      [$this->manager(1), $this->manager(3)],
    );

    $deleted = $this->manager(2);
    $response = (new ManagerService($repository, $this->transactions(1, 0)))->DeleteManager($deleted);

    $this->assertTrue($response->success);
    $this->assertSame([1, 3], array_column(array_map(fn($m) => (array) $m, $response->managers), 'manager_id'));
    // The manager parameter is not clobbered by the loop over the remaining managers
    $this->assertSame(2, $deleted->manager_id);
  }

  public function testDeleteFailsAndRollsBackWhenTheRenumberFails(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetManagersByDraftOrder')->willReturn([$this->manager(1)]);
    $repository->method('ReorderManagers')->willThrowException(new \Exception('Unable to update manager order for manager #1'));

    $response = (new ManagerService($repository, $this->transactions(0, 1)))->DeleteManager($this->manager(2));

    $this->assertFalse($response->success);
    $this->assertSame(['Unable to update manager order for manager #1'], $response->errors);
  }

  public function testAnErrorInADeleteRollsBackAndIsRethrown(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('DeleteManager')->willThrowException(new \TypeError('Bad manager id'));

    $this->expectException(\TypeError::class);

    (new ManagerService($repository, $this->transactions(0, 1)))->DeleteManager($this->manager(2));
  }
}
