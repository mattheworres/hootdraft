<?php

namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Entities\Manager;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Validators\ManagerValidator;
use PHPUnit\Framework\TestCase;

class ManagerValidatorTest extends TestCase
{
  private ManagerRepository $repository;
  private ManagerValidator $sut;

  protected function setUp(): void
  {
    $this->repository = $this->createStub(ManagerRepository::class);
    $this->repository->method('GetNumberOfCurrentManagers')->willReturn(2);
    $this->repository->method('NameIsUnique')->willReturn(true);
    $this->repository->method('ManagerExists')->willReturn(true);
    $this->sut = new ManagerValidator($this->repository);
  }

  private function manager(?string $name, int $draftId = 5, ?int $id = null): Manager
  {
    $manager = new Manager();
    $manager->manager_id = $id;
    $manager->draft_id = $draftId;
    $manager->manager_name = $name;

    return $manager;
  }

  public function testCreateAcceptsAValidManager(): void
  {
    $this->assertTrue($this->sut->IsManagerValidForCreate(5, $this->manager('Alice'))->success);
  }

  public function testCreateRejectsMissingLongAndDuplicateNames(): void
  {
    $this->assertSame(['One or more missing fields.'], $this->sut->IsManagerValidForCreate(5, $this->manager(null))->errors);
    $this->assertSame(['Manager name is above maximum length.'], $this->sut->IsManagerValidForCreate(5, $this->manager(str_repeat('a', 256)))->errors);

    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(2);
    $repository->method('NameIsUnique')->willReturn(false);

    $response = (new ManagerValidator($repository))->IsManagerValidForCreate(5, $this->manager('Alice'));
    $this->assertSame(["Manager name 'Alice' already exists - please choose another name."], $response->errors);
  }

  public function testCreateRejectsTheTwentyFirstManager(): void
  {
    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(20);
    $repository->method('NameIsUnique')->willReturn(true);

    $response = (new ManagerValidator($repository))->IsManagerValidForCreate(5, $this->manager('Alice'));

    $this->assertSame(['Unable to add more managers - 20 is the maximum number of managers allowed.'], $response->errors);
  }

  public function testCreateManyChecksDuplicatesAndTheTwentyManagerLimit(): void
  {
    $this->assertTrue($this->sut->AreManagersValidForCreate(5, [$this->manager('A'), $this->manager('B')])->success);

    $duplicate = $this->sut->AreManagersValidForCreate(5, [$this->manager('A'), $this->manager('A')]);
    $this->assertSame(['Two or more managers had the same name - please ensure all managers have unique names.'], $duplicate->errors);

    $tooMany = array_map(fn($i) => $this->manager("M$i"), range(1, 19));
    $response = $this->sut->AreManagersValidForCreate(5, $tooMany);
    $this->assertSame(
      ['Unable to add 19 new managers - the maximum number of managers is 20. Draft currently has 2, so please remove 1 manager to continue.'],
      $response->errors
    );

    $missing = $this->sut->AreManagersValidForCreate(5, [$this->manager('A'), $this->manager(null)]);
    $this->assertSame(['One or more missing fields for manager #2.'], $missing->errors);
  }

  public function testReorderRequiresEveryManagerAndKnownIds(): void
  {
    $this->assertTrue($this->sut->AreManagerIdsValidForOrdering(5, [3, 4])->success);

    $empty = $this->sut->AreManagerIdsValidForOrdering(5, []);
    $this->assertContains('One or more missing fields.', $empty->errors);

    $repository = $this->createStub(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(2);
    $repository->method('ManagerExists')->willReturn(false);

    $unknown = (new ManagerValidator($repository))->AreManagerIdsValidForOrdering(5, [3, 4]);
    $this->assertFalse($unknown->success);
    $this->assertSame(['Manager #3 does not exist.', 'Manager #4 does not exist.'], $unknown->errors);
  }

  public function testReorderRejectsTheSameManagerTwice(): void
  {
    $response = $this->sut->AreManagerIdsValidForOrdering(5, [3, 3]);

    $this->assertFalse($response->success);
    $this->assertSame(['Two or more of the managers given are the same manager.'], $response->errors);
  }

  public function testCreateManyTreatsNamesThatDifferOnlyByCaseAsDuplicates(): void
  {
    $response = $this->sut->AreManagersValidForCreate(5, [$this->manager('Bob'), $this->manager('bob')]);

    $this->assertFalse($response->success);
    $this->assertSame(['Two or more managers had the same name - please ensure all managers have unique names.'], $response->errors);
  }

  public function testReorderWithTheWrongCountFailsWithoutCheckingTheIds(): void
  {
    $repository = $this->createMock(ManagerRepository::class);
    $repository->method('GetNumberOfCurrentManagers')->willReturn(2);
    $repository->expects($this->never())->method('ManagerExists');

    $response = (new ManagerValidator($repository))->AreManagerIdsValidForOrdering(5, [3]);

    $this->assertSame(['Incorrect number of managers given. Draft #5 has 2 managers, but 1 were received.'], $response->errors);
    $this->assertFalse($response->success);
  }

  public function testUpdateChecksOwnershipAndFields(): void
  {
    $draft = new Draft();
    $draft->draft_id = 5;

    $this->assertTrue($this->sut->IsManagerValidForUpdate($draft, $this->manager('Alice', 5, 9))->success);

    $other = $this->sut->IsManagerValidForUpdate($draft, $this->manager('Alice', 6, 9));
    $this->assertSame(['Manager does not belong to draft #5'], $other->errors);

    $missing = $this->sut->IsManagerValidForUpdate($draft, $this->manager('', 5, 9));
    $this->assertSame(['One or more missing fields.'], $missing->errors);
  }
}
