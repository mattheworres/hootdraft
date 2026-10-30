<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Entities\LoginUser;
use HootDraft\Domain\Repositories\LoginUserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The repository's reading and writing of the Email Verified columns against the migrated test DB (see DatabaseTest
 * for setup). Every user these tests create has an @repository-test.example email and is deleted afterwards.
 */
#[Group('database')]
class LoginUserRepositoryTest extends KernelTestCase
{
  protected function setUp(): void
  {
    self::bootKernel();
    $this->deleteTestUsers();
  }

  protected function tearDown(): void
  {
    $this->deleteTestUsers();
    parent::tearDown();
  }

  private function deleteTestUsers(): void
  {
    static::getContainer()->get(Connection::class)->executeStatement("DELETE FROM users WHERE email LIKE '%@repository-test.example'");
  }

  private function repository(): LoginUserRepository
  {
    return static::getContainer()->get(LoginUserRepository::class);
  }

  private function newUser(): LoginUser
  {
    $user = new LoginUser();
    $user->email = 'alice@repository-test.example';
    $user->password = 'x';
    $user->salt = '';
    $user->name = 'Alice';
    $user->roles = ['ROLE_COMMISH'];
    $user->enabled = 1;
    $user->emailVerified = 0;
    $user->emailVerificationKey = 'AAAAAAAAAAAAAAAA';
    $user->emailVerificationKeyCreated = '2026-10-30 12:00:00';
    $user->resetKey = 'BBBBBBBBBBBBBBBB';
    $user->resetKeyCreated = '2026-10-30 13:00:00';

    return $user;
  }

  public function testCreateAndLoadRoundTripTheEmailVerifiedColumns(): void
  {
    $this->repository()->Create($this->newUser());

    $loaded = $this->repository()->Load('alice@repository-test.example');

    $this->assertSame([1, 0], [(int) $loaded->enabled, (int) $loaded->emailVerified]);
    $this->assertSame('AAAAAAAAAAAAAAAA', $loaded->emailVerificationKey);
    $this->assertSame('2026-10-30 12:00:00', $loaded->emailVerificationKeyCreated);
    $this->assertSame('BBBBBBBBBBBBBBBB', $loaded->resetKey);
    $this->assertSame('2026-10-30 13:00:00', $loaded->resetKeyCreated);
  }

  public function testUpdateWritesTheEmailVerifiedColumns(): void
  {
    $user = $this->repository()->Create($this->newUser());

    $user->emailVerified = 1;
    $user->emailVerificationKey = null;
    $user->emailVerificationKeyCreated = null;
    $user->resetKey = null;
    $user->resetKeyCreated = null;
    $this->repository()->Update($user);

    $loaded = $this->repository()->LoadById($user->id);
    $this->assertSame([1, 1], [(int) $loaded->enabled, (int) $loaded->emailVerified]);
    $this->assertNull($loaded->emailVerificationKey);
    $this->assertNull($loaded->emailVerificationKeyCreated);
    $this->assertNull($loaded->resetKey);
  }

  public function testAdminUserListHidesEveryKey(): void
  {
    $this->repository()->Create($this->newUser());

    $listed = array_values(array_filter(
      $this->repository()->LoadAll(),
      fn(LoginUser $u) => $u->email === 'alice@repository-test.example',
    ));

    $this->assertCount(1, $listed);
    $fields = get_object_vars($listed[0]);
    foreach (['password', 'salt', 'resetKey', 'resetKeyCreated', 'emailVerificationKey', 'emailVerificationKeyCreated'] as $secret) {
      $this->assertArrayNotHasKey($secret, $fields);
    }
    $this->assertSame(0, (int) $fields['emailVerified']);
  }
}
