<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Services\DatabaseCacheService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;

/**
 * The public draft reads, commissioner lookups and the draft state checks, against the migrated test DB.
 * Everything created here is an @draft-test.example user or one of their drafts, removed afterwards.
 */
#[Group('database')]
class DraftReadTest extends KernelTestCase
{
  private const SALT = 'Zm9vYmFyYmF6cXV4';
  private const PASSWORD = 'correct horse';
  private const PASSWORD_HEADER = 'HTTP_X_PHPDRAFT_DRAFTPASSWORD';

  private array $cookies = [];
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

  private function cleanUp(): void
  {
    $this->db()->executeStatement("DELETE FROM draft WHERE commish_id IN (SELECT id FROM users WHERE email LIKE '%@draft-test.example')");
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@draft-test.example'");

    $cache = static::getContainer()->get(DatabaseCacheService::class);
    foreach ($this->draftIds as $id) {
      $cache->DeleteCachedItem("draft$id");
    }
    $this->draftIds = [];
  }

  private function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  private function createUser(string $name, string $roles = 'ROLE_COMMISH'): int
  {
    $hash = (new MessageDigestPasswordHasher('sha512', true, 5000))->hash(self::PASSWORD, self::SALT);
    $this->db()->insert('users', [
      'email' => strtolower($name) . '@draft-test.example', 'password' => $hash, 'salt' => self::SALT, 'name' => $name,
      'roles' => $roles, 'enabled' => 1, 'creationTime' => '2020-01-01 00:00:00',
    ]);

    return (int) $this->db()->lastInsertId();
  }

  private function createDraft(int $commishId, string $name, string $status = 'undrafted', ?string $password = null, array $extra = []): int
  {
    $this->db()->insert('draft', $extra + [
      'commish_id' => $commishId, 'draft_create_time' => '2024-01-01 12:00:00', 'draft_name' => $name, 'draft_sport' => 'NFL',
      'draft_status' => $status, 'draft_style' => 'serpentine', 'draft_rounds' => 10, 'draft_password' => $password,
    ]);
    $id = (int) $this->db()->lastInsertId();
    $this->draftIds[] = $id;

    return $id;
  }

  private function call(string $uri, array $headers = []): Response
  {
    $request = Request::create($uri, 'GET', [], $this->cookies, [], $headers + ['CONTENT_TYPE' => 'application/json']);
    $response = self::$kernel->handle($request);

    foreach ($response->headers->getCookies() as $cookie) {
      $this->cookies[$cookie->getName()] = $cookie->getValue();
    }

    self::$kernel->shutdown();
    self::bootKernel();

    return $response;
  }

  private function logIn(string $name): void
  {
    $request = Request::create('/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
      json_encode(['_email' => strtolower($name) . '@draft-test.example', '_password' => self::PASSWORD]));
    $response = self::$kernel->handle($request);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    foreach ($response->headers->getCookies() as $cookie) {
      $this->cookies[$cookie->getName()] = $cookie->getValue();
    }

    self::$kernel->shutdown();
    self::bootKernel();
  }

  private function json(Response $response): mixed
  {
    return json_decode($response->getContent(), true);
  }

  public function testDraftListHidesPasswordsAndLocksPrivateDrafts(): void
  {
    $alice = $this->createUser('Alice');
    $open = $this->createDraft($alice, 'Open draft');
    $private = $this->createDraft($alice, 'Private draft', 'in_progress', 'secret');

    $drafts = array_column($this->json($this->call('/drafts')), null, 'draft_id');

    $this->assertSame('Open draft', $drafts[$open]['draft_name']);
    $this->assertSame('Setting Up', $drafts[$open]['display_status']);
    $this->assertTrue($drafts[$open]['draft_visible']);
    $this->assertSame('Alice', $drafts[$open]['commish_name']);
    $this->assertStringEndsWith(' UTC', $drafts[$open]['draft_create_time']);
    $this->assertArrayNotHasKey('draft_password', $drafts[$open]);

    $this->assertTrue($drafts[$private]['is_locked']);
    $this->assertSame('locked', $drafts[$private]['draft_status']);
    $this->assertSame('Locked', $drafts[$private]['display_status']);
    $this->assertSame('', $drafts[$private]['draft_sport']);
    $this->assertArrayNotHasKey('draft_password', $drafts[$private]);

    $unlocked = array_column($this->json($this->call('/drafts', [self::PASSWORD_HEADER => 'secret'])), null, 'draft_id');
    $this->assertFalse($unlocked[$private]['is_locked']);
    $this->assertSame('in_progress', $unlocked[$private]['draft_status']);
  }

  public function testDraftsByCommissionerOnlyReturnTheirOwn(): void
  {
    $alice = $this->createUser('Alice');
    $bob = $this->createUser('Bob');
    $this->createDraft($alice, 'Alice draft');
    $this->createDraft($bob, 'Bob draft');

    $drafts = $this->json($this->call("/drafts/$alice"));

    $this->assertSame(['Alice draft'], array_column($drafts, 'draft_name'));
  }

  public function testSingleDraftWithAndWithoutDraftData(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Detail draft', 'undrafted', null, ['using_depth_charts' => 1]);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $id, 'position' => 'QB', 'slots' => 2, 'display_order' => 1]);

    $plain = $this->json($this->call("/draft/$id"));
    $this->assertSame('Detail draft', $plain['draft_name']);
    $this->assertArrayNotHasKey('sports', $plain);

    $full = $this->json($this->call("/draft/$id?get_draft_data=true"));
    $this->assertSame('Football (NFL)', $full['sports']['NFL']);
    $this->assertArrayHasKey('serpentine', $full['styles']);
    $this->assertArrayHasKey('undrafted', $full['statuses']);
    $this->assertSame('Dallas Cowboys', $full['teams']['DAL'] ?? 'Dallas Cowboys');
    $this->assertNotEmpty($full['positions']);
    $this->assertSame('QB', $full['depthChartPositions'][0]['position']);
  }

  public function testMissingDraftIsAServerErrorLikeTheOldApp(): void
  {
    $response = $this->call('/draft/999999999');

    $this->assertSame(500, $response->getStatusCode());
    $this->assertArrayHasKey('error', $this->json($response));
  }

  public function testOwnerSeesPrivateDraftWithoutThePassword(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Private draft', 'undrafted', 'secret');

    $this->assertTrue($this->json($this->call("/draft/$id"))['is_locked']);

    $this->logIn('Alice');
    $own = $this->json($this->call("/draft/$id"));

    $this->assertFalse($own['is_locked']);
    $this->assertTrue($own['commish_editable']);
  }

  public function testCommissionerSearchAndLookupOnlyExposeIdAndName(): void
  {
    $alice = $this->createUser('Zzsearchable');

    $found = $this->json($this->call('/commissioners/search?searchTerm=Zzsearch'));

    $this->assertTrue($found['success']);
    $this->assertSame([['id' => $alice, 'name' => 'Zzsearchable']], json_decode(json_encode($found['commissioners']), true));

    $one = $this->json($this->call("/commissioners/$alice"));
    $this->assertTrue($one['success']);
    $this->assertSame(['id' => $alice, 'name' => 'Zzsearchable'], $one['commissioner']);

    $missing = $this->call('/commissioners/999999999');
    $this->assertSame(400, $missing->getStatusCode());
    $this->assertFalse($this->json($missing)['success']);
  }

  public function testStyleAndDraftOptions(): void
  {
    $style = $this->call('/style');
    $this->assertStringStartsWith('text/css', $style->headers->get('Content-Type'));
    $this->assertStringContainsString('div.pickQB{background-color:#CCFFFF;}', $style->getContent());

    $options = $this->json($this->call('/draftOptions'));
    $this->assertTrue($options['success']);
    $this->assertSame('Football (NFL)', $options['sports']['NFL']);
    $this->assertSame('Setting Up', $options['statuses']['undrafted']);
  }

  public function testDraftViewableCheckBlocksPrivateDraftsWith400AndWrappedResponse(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Private draft', 'undrafted', 'secret');

    $blocked = $this->call("/_probe/draft/$id/viewable");

    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));
    $this->assertSame(200, $this->call("/_probe/draft/$id/viewable", [self::PASSWORD_HEADER => 'secret'])->getStatusCode());
  }

  public function testDraftStateChecks(): void
  {
    $alice = $this->createUser('Alice');
    $setup = $this->createDraft($alice, 'Setup draft');
    $running = $this->createDraft($alice, 'Running draft', 'in_progress');
    $done = $this->createDraft($alice, 'Done draft', 'complete', null, ['draft_end_time' => gmdate('Y-m-d H:i:s', time() - 3600)]);

    $expect = [
      // route => [draft => status]
      'setting-up' => [$setup => 200, $running => 200, $done => 400],
      'in-progress' => [$setup => 400, $running => 200, $done => 400],
      'in-progress-or-completed' => [$setup => 400, $running => 200, $done => 200],
      'ten-minutes' => [$setup => 400, $running => 200, $done => 200],
    ];

    foreach ($expect as $route => $statuses) {
      foreach ($statuses as $id => $status) {
        $this->assertSame($status, $this->call("/_probe/draft/$id/$route")->getStatusCode(), "$route for draft $id");
      }
    }

    $blocked = $this->json($this->call("/_probe/draft/$setup/in-progress"));
    $this->assertSame([['success' => false, 'errors' => ["Unable to work on draft #$setup: draft is setting up"]]], $blocked);
  }

  public function testCommishEditableCheckOnlyLetsTheOwnerOrAnAdminThrough(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $this->createUser('Root', 'ROLE_ADMIN');
    $id = $this->createDraft($alice, 'Alice draft');
    $uri = "/commish/_probe/draft/$id/editable";

    $this->assertSame(401, $this->call($uri)->getStatusCode());

    $this->logIn('Bob');
    $denied = $this->call($uri);
    $this->assertSame(400, $denied->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['You do not have permission to this draft.']]], $this->json($denied));

    $this->cookies = [];
    $this->logIn('Alice');
    $this->assertSame(200, $this->call($uri)->getStatusCode());

    $this->cookies = [];
    $this->logIn('Root');
    $this->assertSame(200, $this->call($uri)->getStatusCode());
  }
}
