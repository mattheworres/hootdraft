<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Services\DatabaseCacheService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;

/**
 * Shared setup for the commissioner draft routes: users, an in-progress NFL draft with two managers and four picks,
 * a logged-in session and JSON calls. Subclasses name their own email domain so each cleans up only its rows.
 */
abstract class CommishDraftTestCase extends KernelTestCase
{
  protected const PASSWORD = 'secret-pass-1';
  protected const SALT = 'test-salt';

  /** The domain every user this test creates uses, e.g. 'pick-ops-test.example' */
  abstract protected static function domain(): string;

  /** Set by a subclass that inspects services of the kernel a call ran on, e.g. the captured emails */
  protected bool $keepKernelBetweenCalls = false;

  protected array $cookies = [];
  private array $draftIds = [];

  protected function setUp(): void
  {
    self::bootKernel();
    $this->cookies = [];
    $this->cleanUp();
  }

  protected function tearDown(): void
  {
    $this->cleanUp();
    parent::tearDown();
  }

  protected function cleanUp(): void
  {
    $domain = static::domain();
    $ids = $this->db()->fetchFirstColumn("SELECT draft_id FROM draft WHERE commish_id IN (SELECT id FROM users WHERE email LIKE '%@$domain')");
    foreach ($ids as $id) {
      $this->db()->executeStatement('DELETE FROM round_times WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM depth_chart_positions WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM trade_assets WHERE trade_id IN (SELECT trade_id FROM trades WHERE draft_id = ?)', [$id]);
      $this->db()->executeStatement('DELETE FROM trades WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM draft_stats WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM players WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM managers WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM draft WHERE draft_id = ?', [$id]);
    }
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@$domain'");

    $cache = static::getContainer()->get(DatabaseCacheService::class);
    foreach (array_merge($this->draftIds, $ids) as $id) {
      $cache->DeleteCachedItem("draft$id");
    }
    $this->draftIds = [];
  }

  protected function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  protected function createUser(string $name, string $roles = 'ROLE_COMMISH'): int
  {
    $hash = (new MessageDigestPasswordHasher('sha512', true, 5000))->hash(self::PASSWORD, self::SALT);
    $this->db()->insert('users', [
      'email' => strtolower($name) . '@' . static::domain(), 'password' => $hash, 'salt' => self::SALT, 'name' => $name,
      'roles' => $roles, 'enabled' => 1, 'creationTime' => '2020-01-01 00:00:00',
    ]);

    return (int) $this->db()->lastInsertId();
  }

  /** $extra overrides any column of the default row, e.g. ['draft_name' => 'Open draft', 'draft_rounds' => 10] */
  protected function createDraft(int $commishId, string $status = 'in_progress', ?string $password = null, array $extra = []): int
  {
    $this->db()->insert('draft', $extra + [
      'commish_id' => $commishId, 'draft_create_time' => '2024-01-01 12:00:00', 'draft_name' => 'Commish ops draft', 'draft_sport' => 'NFL',
      'draft_status' => $status, 'draft_style' => 'standard', 'draft_rounds' => 2, 'draft_password' => $password,
      'draft_current_round' => 1, 'draft_current_pick' => 1, 'draft_counter' => 0,
      'draft_start_time' => '2024-01-01 12:00:00',
    ]);
    $id = (int) $this->db()->lastInsertId();
    $this->draftIds[] = $id;

    return $id;
  }

  protected function createManager(int $draftId, string $name, int $order): int
  {
    $this->db()->insert('managers', ['draft_id' => $draftId, 'manager_name' => $name, 'draft_order' => $order]);

    return (int) $this->db()->lastInsertId();
  }

  protected function createPick(int $draftId, int $managerId, int $round, int $pick, ?array $player = null, ?int $counter = null): int
  {
    $row = ['draft_id' => $draftId, 'manager_id' => $managerId, 'player_round' => $round, 'player_pick' => $pick];
    if ($player) {
      $row += $player + ['pick_time' => '2024-01-01 12:05:00', 'pick_duration' => 300, 'player_counter' => $counter];
    }
    $this->db()->insert('players', $row);

    return (int) $this->db()->lastInsertId();
  }

  /**
   * A standard 2-round draft between Team A and Team B with none of the four picks made yet, owned by Alice.
   * Returns [draft, A, B, pick ids] (picks: A, B, B, A for picks 1 to 4).
   */
  protected function seedDraft(string $status = 'in_progress', ?string $password = null): array
  {
    $alice = (int) ($this->db()->fetchOne("SELECT id FROM users WHERE email = ?", ['alice@' . static::domain()]) ?: $this->createUser('Alice'));
    $draft = $this->createDraft($alice, $status, $password);
    $a = $this->createManager($draft, 'Team A', 1);
    $b = $this->createManager($draft, 'Team B', 2);
    $picks = [
      $this->createPick($draft, $a, 1, 1),
      $this->createPick($draft, $b, 1, 2),
      $this->createPick($draft, $b, 2, 3),
      $this->createPick($draft, $a, 2, 4),
    ];

    return [$draft, $a, $b, $picks];
  }

  protected function call(string $method, string $uri, ?array $body = null, array $headers = []): Response
  {
    $server = $headers + ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    $request = Request::create($uri, $method, [], $this->cookies, [], $server, $body === null ? null : json_encode($body));
    $response = self::$kernel->handle($request);

    foreach ($response->headers->getCookies() as $cookie) {
      if ($cookie->getValue() === null || $cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
        unset($this->cookies[$cookie->getName()]);
      } else {
        $this->cookies[$cookie->getName()] = $cookie->getValue();
      }
    }

    if (!$this->keepKernelBetweenCalls) {
      self::$kernel->shutdown();
      self::bootKernel();
    }

    return $response;
  }

  /** A multipart form post, for the file upload route. $files maps field name to an UploadedFile. */
  protected function upload(string $uri, array $fields, array $files): Response
  {
    $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    $request = Request::create($uri, 'POST', $fields, $this->cookies, $files, $server);
    $response = self::$kernel->handle($request);

    self::$kernel->shutdown();
    self::bootKernel();

    return $response;
  }

  protected function logIn(string $name, string $password = self::PASSWORD): Response
  {
    $this->cookies = [];

    return $this->call('POST', '/login', ['_email' => strtolower($name) . '@' . static::domain(), '_password' => $password]);
  }

  /** logIn() for tests that need a session, failing with the login response when it is refused */
  protected function logInSuccessfully(string $name): void
  {
    $response = $this->logIn($name);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
  }

  protected function json(Response $response): mixed
  {
    return json_decode($response->getContent(), true);
  }
}
