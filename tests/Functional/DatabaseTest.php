<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Needs a migrated DB: the Docker box, or any DB matching .env. phpunit.xml forces APP_ENV=test, and Dotenv
 * skips .env.local in that env, so outside Docker put credentials in .env.test.local instead.
 * Run without a DB via: phpunit --exclude-group database
 */
#[Group('database')]
class DatabaseTest extends KernelTestCase
{
  public function testDbalAndNativePdoShareOneWorkingConnection(): void
  {
    self::bootKernel();
    $container = static::getContainer();

    $this->assertSame(1, (int) $container->get(Connection::class)->fetchOne('SELECT 1'));
    $this->assertSame('1', (string) $container->get(\PDO::class)->query('SELECT 1')->fetchColumn());
  }

  public function testMigrationsHaveRun(): void
  {
    self::bootKernel();

    $tables = static::getContainer()->get(Connection::class)->createSchemaManager()->listTableNames();

    foreach (['users', 'draft', 'managers', 'players', 'trades', 'phinxlog'] as $table) {
      $this->assertContains($table, $tables);
    }
  }
}
