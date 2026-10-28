<?php
namespace HootDraft\Tests\Unit\Domain;

use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Repositories\LoginUserRepository;
use HootDraft\Domain\Services\SaltService;
use HootDraft\Domain\Validators\LoginUserValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/** The admin user update rules. The unauthenticated flows are covered through AuthenticationTest. */
class LoginUserValidatorTest extends TestCase {
  private LoginUserRepository $repository;
  private LoginUserValidator $sut;

  protected function setUp(): void {
    $this->repository = $this->createStub(LoginUserRepository::class);
    $this->repository->method('GetRoles')->willReturn(['ROLE_COMMISH' => 'Commissioner', 'ROLE_ADMIN' => 'Administrator']);
    $this->repository->method('NameIsUnique')->willReturn(true);
    $this->repository->method('EmailIsUnique')->willReturn(true);
    $this->repository->method('LoadById')->willReturnCallback(function ($id) {
      if ($id != 7) {
        throw new \Exception("User #$id does not exist.");
      }

      return $this->user(7, 'old@example.com', 'Old Name', 'ROLE_COMMISH');
    });

    $this->sut = new LoginUserValidator($this->repository, new SaltService(), $this->createStub(PasswordHasherFactoryInterface::class));
  }

  private function user(int $id, string $email, string $name, string|array $roles): LoginUser {
    $user = new LoginUser();
    $user->id = $id;
    $user->email = $email;
    $user->name = $name;
    $user->roles = $roles;

    return $user;
  }

  public function testAnUnknownUserIsInvalid(): void {
    $result = $this->sut->IsAdminUserUpdateValid($this->user(99, 'a@example.com', 'A', 'ROLE_COMMISH'));

    $this->assertFalse($result->success);
    $this->assertSame(["Invalid user."], $result->errors);
  }

  public function testAValidUpdatePasses(): void {
    $result = $this->sut->IsAdminUserUpdateValid($this->user(7, 'new@example.com', 'New Name', 'ROLE_COMMISH,ROLE_ADMIN'));

    $this->assertTrue($result->success);
    $this->assertSame([], $result->errors);
  }

  public function testRolesMayArriveAsAnArray(): void {
    $this->assertTrue($this->sut->IsAdminUserUpdateValid($this->user(7, 'old@example.com', 'N', ['ROLE_ADMIN']))->success);
  }

  public function testEmailIsRequired(): void {
    $result = $this->sut->IsAdminUserUpdateValid($this->user(7, '', 'N', 'ROLE_COMMISH'));

    $this->assertFalse($result->success);
    $this->assertContains("Email address is missing.", $result->errors);
  }

  public function testAChangedEmailMustBeValid(): void {
    $result = $this->sut->IsAdminUserUpdateValid($this->user(7, 'not-an-email', 'N', 'ROLE_COMMISH'));

    $this->assertFalse($result->success);
    $this->assertContains("Email invalid.", $result->errors);
  }

  public function testAChangedEmailMustBeUnique(): void {
    $repository = $this->createStub(LoginUserRepository::class);
    $repository->method('GetRoles')->willReturn(['ROLE_COMMISH' => 'Commissioner']);
    $repository->method('NameIsUnique')->willReturn(true);
    $repository->method('EmailIsUnique')->willReturn(false);
    $repository->method('LoadById')->willReturn($this->user(7, 'old@example.com', 'Old', 'ROLE_COMMISH'));
    $sut = new LoginUserValidator($repository, new SaltService(), $this->createStub(PasswordHasherFactoryInterface::class));

    $this->assertContains("Email already registered.", $sut->IsAdminUserUpdateValid($this->user(7, 'taken@example.com', 'N', 'ROLE_COMMISH'))->errors);
    $this->assertTrue($sut->IsAdminUserUpdateValid($this->user(7, 'old@example.com', 'N', 'ROLE_COMMISH'))->success, 'an unchanged email is not checked');
  }

  public function testNameLengthAndUniqueness(): void {
    $repository = $this->createStub(LoginUserRepository::class);
    $repository->method('GetRoles')->willReturn(['ROLE_COMMISH' => 'Commissioner']);
    $repository->method('NameIsUnique')->willReturn(false);
    $repository->method('LoadById')->willReturn($this->user(7, 'old@example.com', 'Old', 'ROLE_COMMISH'));
    $sut = new LoginUserValidator($repository, new SaltService(), $this->createStub(PasswordHasherFactoryInterface::class));

    $result = $sut->IsAdminUserUpdateValid($this->user(7, 'old@example.com', str_repeat('x', 101), 'ROLE_COMMISH'));

    $this->assertSame(["Name is above maximum length", "Name already taken."], $result->errors);
  }

  public function testOnlyKnownRolesAreAccepted(): void {
    $this->assertSame(["Role invalid."], $this->sut->IsAdminUserUpdateValid($this->user(7, 'old@example.com', 'N', 'ROLE_ADMIN,ROLE_ROOT'))->errors);
    $this->assertSame(["Role invalid."], $this->sut->IsAdminUserUpdateValid($this->user(7, 'old@example.com', 'N', ''))->errors);
  }

  public function testThePayloadNeedsEnabledAndScalarFields(): void {
    $valid = ['email' => 'a@example.com', 'name' => 'A', 'roles' => 'ROLE_COMMISH', 'enabled' => 1];

    $this->assertTrue($this->sut->IsAdminUserPayloadValid($valid)->success);
    $this->assertTrue($this->sut->IsAdminUserPayloadValid(['enabled' => 0, 'roles' => ['ROLE_COMMISH']])->success, 'roles and empty fields are left to the update rules');

    foreach ([['email' => 'a@example.com', 'name' => ['A'], 'enabled' => 1], ['email' => ['a'], 'enabled' => 1], ['enabled' => ['1']]] as $payload) {
      $this->assertSame(["Invalid user fields."], $this->sut->IsAdminUserPayloadValid($payload)->errors);
    }

    unset($valid['enabled']);
    $result = $this->sut->IsAdminUserPayloadValid($valid);
    $this->assertFalse($result->success);
    $this->assertSame(["Invalid user fields."], $result->errors);
  }

  public function testAnEmailThatOnlyDiffersInCaseIsNotChecked(): void {
    $repository = $this->createMock(LoginUserRepository::class);
    $repository->method('GetRoles')->willReturn(['ROLE_COMMISH' => 'Commissioner']);
    $repository->method('NameIsUnique')->willReturn(true);
    $repository->method('LoadById')->willReturn($this->user(7, 'Bob@Example.com', 'Bob', 'ROLE_COMMISH'));
    $repository->expects($this->never())->method('EmailIsUnique');
    $sut = new LoginUserValidator($repository, new SaltService(), $this->createStub(PasswordHasherFactoryInterface::class));

    $this->assertTrue($sut->IsAdminUserUpdateValid($this->user(7, 'bob@example.com', 'Bobby', 'ROLE_COMMISH'))->success);
  }

  public function testTheEmailUniquenessCheckLeavesTheUsersOwnRowOut(): void {
    $repository = $this->createMock(LoginUserRepository::class);
    $repository->method('GetRoles')->willReturn(['ROLE_COMMISH' => 'Commissioner']);
    $repository->method('NameIsUnique')->willReturn(true);
    $repository->method('LoadById')->willReturn($this->user(7, 'old@example.com', 'Old', 'ROLE_COMMISH'));
    $repository->expects($this->once())->method('EmailIsUnique')->with('new@example.com', 7)->willReturn(true);
    $sut = new LoginUserValidator($repository, new SaltService(), $this->createStub(PasswordHasherFactoryInterface::class));

    $this->assertTrue($sut->IsAdminUserUpdateValid($this->user(7, 'new@example.com', 'Old', 'ROLE_COMMISH'))->success);
  }

  public function testAdminsCannotRemoveTheirOwnAccess(): void {
    $message = "You can't remove your own admin role or disable your own account.";

    $demoted = $this->user(7, 'old@example.com', 'N', 'ROLE_COMMISH');
    $demoted->enabled = 1;
    $this->assertSame([$message], $this->sut->IsAdminUserUpdateValid($demoted, 7)->errors);

    $disabled = $this->user(7, 'old@example.com', 'N', 'ROLE_COMMISH, ROLE_ADMIN');
    $disabled->enabled = 0;
    $this->assertSame([$message], $this->sut->IsAdminUserUpdateValid($disabled, 7)->errors);

    $this->assertTrue($this->sut->IsAdminUserUpdateValid($demoted, 8)->success, 'changing someone else is fine');
    $this->assertTrue($this->sut->IsAdminUserUpdateValid($demoted)->success, 'no current user to compare with');

    $staying = $this->user(7, 'old@example.com', 'N', 'ROLE_COMMISH, ROLE_ADMIN');
    $staying->enabled = 1;
    $this->assertTrue($this->sut->IsAdminUserUpdateValid($staying, 7)->success, 'roles are trimmed, so a spaced list still counts');
  }
}
