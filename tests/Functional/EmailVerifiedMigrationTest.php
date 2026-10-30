<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs the email-verified migration against the test DB (see DatabaseTest for setup): rolls it back, seeds users in
 * the old shape, migrates, and checks each row. The DB is migrated to the latest version again afterwards.
 * Every user these tests create has an @migration-test.example email and is deleted afterwards.
 */
#[Group('database')]
class EmailVerifiedMigrationTest extends KernelTestCase
{
  private const MIGRATION = 20261031000000;
  private const PREVIOUS = 20261030000000;
  private const KEY = 'AAAAAAAAAAAAAAAA';
  private const CREATED = '2026-10-30 12:00:00';

  private Manager $manager;

  protected function setUp(): void
  {
    self::bootKernel();

    // The config file path is what %%PHINX_CONFIG_DIR%% in the migration paths resolves against
    $configFile = dirname(__DIR__, 2) . '/phinx.php';
    $this->manager = new Manager(new Config(require $configFile, $configFile), new ArrayInput([]), new BufferedOutput());
    $this->manager->rollback('production', self::PREVIOUS);
    $this->deleteTestUsers();
  }

  protected function tearDown(): void
  {
    $this->manager->migrate('production');
    $this->deleteTestUsers();
    parent::tearDown();
  }

  private function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  private function deleteTestUsers(): void
  {
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@migration-test.example'");
  }

  /** A user in the shape before the migration, where `enabled` also meant "email verified". */
  private function seedOldUser(string $name, int $enabled, ?string $key): void
  {
    $this->db()->insert('users', [
      'email' => "$name@migration-test.example", 'password' => 'x', 'salt' => '', 'name' => $name, 'roles' => 'ROLE_COMMISH',
      'enabled' => $enabled, 'verificationKey' => $key, 'verificationKeyCreated' => $key === null ? null : self::CREATED,
      'creationTime' => '2020-01-01 00:00:00',
    ]);
  }

  private function row(string $name): array
  {
    return $this->db()->fetchAssociative('SELECT * FROM users WHERE email = ?', ["$name@migration-test.example"]);
  }

  public function testEnabledUserBecomesEmailVerified(): void
  {
    $this->seedOldUser('enabled', 1, null);
    $this->seedOldUser('enabledwithreset', 1, self::KEY);

    $this->manager->migrate('production', self::MIGRATION);

    $plain = $this->row('enabled');
    $this->assertSame([1, 1], [(int) $plain['enabled'], (int) $plain['emailVerified']]);
    $this->assertNull($plain['emailVerificationKey']);

    // A pending password reset stays a reset
    $reset = $this->row('enabledwithreset');
    $this->assertSame([1, 1], [(int) $reset['enabled'], (int) $reset['emailVerified']]);
    $this->assertSame(self::KEY, $reset['resetKey']);
    $this->assertSame(self::CREATED, $reset['resetKeyCreated']);
    $this->assertNull($reset['emailVerificationKey']);
  }

  public function testDisabledUserWithPendingKeyBecomesEmailUnverified(): void
  {
    $this->seedOldUser('pendingemail', 0, self::KEY);

    $this->manager->migrate('production', self::MIGRATION);

    $row = $this->row('pendingemail');
    $this->assertSame([1, 0], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertSame(self::KEY, $row['emailVerificationKey']);
    $this->assertSame(self::CREATED, $row['emailVerificationKeyCreated']);
    $this->assertNull($row['resetKey']);
    $this->assertNull($row['resetKeyCreated']);
  }

  public function testDisabledUserWithoutKeyStaysDisabledAndEmailVerified(): void
  {
    $this->seedOldUser('disabled', 0, null);

    $this->manager->migrate('production', self::MIGRATION);

    $row = $this->row('disabled');
    $this->assertSame([0, 1], [(int) $row['enabled'], (int) $row['emailVerified']]);
    $this->assertNull($row['emailVerificationKey']);
    $this->assertNull($row['resetKey']);
  }

  public function testRollbackRestoresTheOldShape(): void
  {
    $this->seedOldUser('enabled', 1, null);
    $this->seedOldUser('enabledwithreset', 1, self::KEY);
    $this->seedOldUser('pendingemail', 0, self::KEY);
    $this->seedOldUser('disabled', 0, null);

    $this->manager->migrate('production', self::MIGRATION);
    $this->manager->rollback('production', self::PREVIOUS);

    $expected = [
      'enabled' => [1, null],
      'enabledwithreset' => [1, self::KEY],
      'pendingemail' => [0, self::KEY],
      'disabled' => [0, null],
    ];
    foreach ($expected as $name => [$enabled, $key]) {
      $row = $this->row($name);
      $this->assertSame($enabled, (int) $row['enabled'], $name);
      $this->assertSame($key, $row['verificationKey'], $name);
      $this->assertSame($key === null ? null : self::CREATED, $row['verificationKeyCreated'], $name);
    }
  }
}
