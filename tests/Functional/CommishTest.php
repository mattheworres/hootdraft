<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Services\DatabaseCacheService;
use HootDraft\Domain\Services\EmailService;
use HootDraft\Tests\Support\CapturingEmailService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;

/** The commissioner's drafts, round timers, profile and invitations, plus the public pick timer, against the test DB. */
#[Group('database')]
class CommishTest extends KernelTestCase
{
  private const SALT = 'Zm9vYmFyYmF6cXV4';
  private const PASSWORD = 'correct horse';

  private array $cookies = [];

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
    $users = "SELECT id FROM users WHERE email LIKE '%@commish-test.example'";
    $cache = static::getContainer()->get(DatabaseCacheService::class);

    foreach ($this->db()->fetchFirstColumn("SELECT draft_id FROM draft WHERE commish_id IN ($users)") as $id) {
      $cache->DeleteCachedItem("draft$id");
      $this->db()->executeStatement('DELETE FROM round_times WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM depth_chart_positions WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM trade_assets WHERE trade_id IN (SELECT trade_id FROM trades WHERE draft_id = ?)', [$id]);
      $this->db()->executeStatement('DELETE FROM trades WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM players WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM managers WHERE draft_id = ?', [$id]);
    }

    $this->db()->executeStatement("DELETE FROM draft WHERE commish_id IN ($users)");
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@commish-test.example'");
  }

  private function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  private function createUser(string $name, string $roles = 'ROLE_COMMISH'): int
  {
    $hash = (new MessageDigestPasswordHasher('sha512', true, 5000))->hash(self::PASSWORD, self::SALT);
    $this->db()->insert('users', [
      'email' => strtolower($name) . '@commish-test.example', 'password' => $hash, 'salt' => self::SALT, 'name' => $name,
      'roles' => $roles, 'enabled' => 1, 'creationTime' => '2020-01-01 00:00:00',
    ]);

    return (int) $this->db()->lastInsertId();
  }

  private function createDraft(int $commishId, string $name, string $status = 'undrafted', array $extra = []): int
  {
    $this->db()->insert('draft', $extra + [
      'commish_id' => $commishId, 'draft_create_time' => '2024-01-01 12:00:00', 'draft_name' => $name, 'draft_sport' => 'NFL',
      'draft_status' => $status, 'draft_style' => 'serpentine', 'draft_rounds' => 3,
    ]);

    return (int) $this->db()->lastInsertId();
  }

  private function call(string $method, string $uri, ?array $body = null): Response
  {
    $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    $request = Request::create($uri, $method, [], $this->cookies, [], $server, $body === null ? null : json_encode($body));
    $response = self::$kernel->handle($request);

    foreach ($response->headers->getCookies() as $cookie) {
      if ($cookie->getValue() === null || $cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
        unset($this->cookies[$cookie->getName()]);
      } else {
        $this->cookies[$cookie->getName()] = $cookie->getValue();
      }
    }

    return $response;
  }

  private function logIn(string $name, string $password = self::PASSWORD): Response
  {
    $this->cookies = [];

    return $this->call('POST', '/login', ['_email' => strtolower($name) . '@commish-test.example', '_password' => $password]);
  }

  private function json(Response $response): mixed
  {
    return json_decode($response->getContent(), true);
  }

  private function newDraftBody(array $overrides = []): array
  {
    return $overrides + ['name' => 'Fresh draft', 'sport' => 'NFL', 'style' => 'serpentine', 'rounds' => 5, 'password' => '', 'using_depth_charts' => false];
  }

  public function testCommishAndProfileRoutesNeedASession(): void
  {
    $this->assertSame(401, $this->call('GET', '/commish')->getStatusCode());
    $this->assertSame(401, $this->call('GET', '/commish/draft/create')->getStatusCode());
    $this->assertSame(401, $this->call('POST', '/commish/draft', $this->newDraftBody())->getStatusCode());
    $this->assertSame(401, $this->call('GET', '/commish/profile')->getStatusCode());
    $this->assertSame(401, $this->call('POST', '/commish/user/invite', ['email' => 'x@commish-test.example', 'name' => 'X'])->getStatusCode());
  }

  public function testIndexReportsTheRoles(): void
  {
    $this->createUser('Alice');
    $this->logIn('Alice');

    $this->assertSame(['authenticated' => true, 'roles' => ['ROLE_COMMISH']], $this->json($this->call('GET', '/commish')));
  }

  public function testGetCreateReturnsTheDefaultsForTheSignedInCommissioner(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');

    $draft = $this->json($this->call('GET', '/commish/draft/create'));

    $this->assertSame('serpentine', $draft['draft_style']);
    $this->assertSame('NFL', $draft['draft_sport']);
    $this->assertSame($alice, $draft['commish_id']);
    $this->assertSame('Alice', $draft['commish_name']);
    $this->assertArrayHasKey('NFL', $draft['sports']);
    $this->assertArrayHasKey('serpentine', $draft['styles']);
  }

  public function testCreateGetUpdateAndDeleteADraft(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');

    $invalid = $this->call('POST', '/commish/draft', $this->newDraftBody(['name' => '', 'rounds' => 0]));
    $this->assertSame(400, $invalid->getStatusCode());
    $this->assertFalse($this->json($invalid)['success']);

    $created = $this->call('POST', '/commish/draft', $this->newDraftBody(['using_depth_charts' => true, 'depthChartPositions' => [['position' => 'QB', 'slots' => 1], ['position' => 'RB', 'slots' => 2]]]));
    $this->assertSame(201, $created->getStatusCode(), $created->getContent());
    $draft = $this->json($created)['draft'];
    $id = (int) $draft['draft_id'];
    $this->assertSame($alice, (int) $draft['commish_id']);
    $this->assertSame('undrafted', $draft['draft_status']);
    $this->assertSame(['QB', 'RB'], $this->db()->fetchFirstColumn('SELECT position FROM depth_chart_positions WHERE draft_id = ? ORDER BY display_order', [$id]));

    $duplicate = $this->call('POST', '/commish/draft', $this->newDraftBody());
    $this->assertSame(400, $duplicate->getStatusCode());

    $badPositions = $this->call('POST', '/commish/draft', $this->newDraftBody(['name' => 'Other', 'using_depth_charts' => true, 'depthChartPositions' => [['position' => 'QB', 'slots' => 1], ['position' => 'qb', 'slots' => 1]]]));
    $this->assertSame(400, $badPositions->getStatusCode());
    $this->assertSame(0, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM draft WHERE draft_name = 'Other'"));

    $loaded = $this->json($this->call('GET', "/commish/draft/$id"));
    $this->assertSame('Fresh draft', $loaded['draft_name']);
    $this->assertCount(2, $loaded['depthChartPositions']);
    $this->assertArrayHasKey('undrafted', $loaded['statuses']);

    $updated = $this->call('PUT', "/commish/draft/$id", $this->newDraftBody(['name' => 'Renamed', 'rounds' => 7, 'using_depth_charts' => true, 'depthChartPositions' => [['position' => 'WR', 'slots' => 3]]]));
    $this->assertSame(200, $updated->getStatusCode(), $updated->getContent());
    $this->assertSame('Renamed', $this->db()->fetchOne('SELECT draft_name FROM draft WHERE draft_id = ?', [$id]));
    $this->assertSame(['WR'], $this->db()->fetchFirstColumn('SELECT position FROM depth_chart_positions WHERE draft_id = ?', [$id]));

    $this->db()->insert('managers', ['draft_id' => $id, 'manager_name' => 'Mgr', 'draft_order' => 1]);
    $this->db()->insert('players', ['draft_id' => $id, 'manager_id' => $this->db()->lastInsertId(), 'player_round' => 1, 'player_pick' => 1]);
    $this->db()->insert('round_times', ['draft_id' => $id, 'is_static_time' => 1, 'round_time_seconds' => 60]);

    $deleted = $this->call('DELETE', "/commish/draft/$id");
    $this->assertSame(200, $deleted->getStatusCode(), $deleted->getContent());
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft WHERE draft_id = ?', [$id]));
    foreach (['managers', 'players', 'round_times', 'depth_chart_positions'] as $table) {
      $this->assertSame(0, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM $table WHERE draft_id = ?", [$id]), $table);
    }
  }

  public function testDepthChartSettingsOnlyChangeWhileSettingUp(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');

    $running = $this->createDraft($alice, 'Running draft', 'in_progress', ['using_depth_charts' => 1]);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $running, 'position' => 'QB', 'slots' => 1, 'display_order' => 0]);
    $positionId = (int) $this->db()->lastInsertId();

    // The rest of the form saves; the depth chart fields in it are ignored
    $body = $this->newDraftBody(['name' => 'Running renamed', 'using_depth_charts' => false, 'depthChartPositions' => [['position' => 'WR', 'slots' => 3]]]);
    $edited = $this->call('PUT', "/commish/draft/$running", $body);
    $this->assertSame(200, $edited->getStatusCode(), $edited->getContent());
    $this->assertSame('Running renamed', $this->db()->fetchOne('SELECT draft_name FROM draft WHERE draft_id = ?', [$running]));
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT using_depth_charts FROM draft WHERE draft_id = ?', [$running]));
    $this->assertSame([$positionId], array_map('intval', $this->db()->fetchFirstColumn('SELECT id FROM depth_chart_positions WHERE draft_id = ?', [$running])));

    // While setting up, turning them off removes the positions
    $setup = $this->createDraft($alice, 'Setup draft', 'undrafted', ['using_depth_charts' => 1]);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $setup, 'position' => 'QB', 'slots' => 1, 'display_order' => 0]);

    $off = $this->call('PUT', "/commish/draft/$setup", $this->newDraftBody(['name' => 'Setup draft', 'using_depth_charts' => false]));
    $this->assertSame(200, $off->getStatusCode(), $off->getContent());
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM depth_chart_positions WHERE draft_id = ?', [$setup]));
  }

  public function testAnotherCommissionerCannotTouchTheDraft(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $id = $this->createDraft($alice, 'Alice draft');
    $this->logIn('Bob');

    $permission = [['success' => false, 'errors' => ['You do not have permission to this draft.']]];

    foreach ([
      ['GET', "/commish/draft/$id", null],
      ['PUT', "/commish/draft/$id", $this->newDraftBody()],
      ['PUT', "/commish/draft/$id/status", ['status' => 'undrafted']],
      ['DELETE', "/commish/draft/$id", null],
      ['GET', "/commish/draft/$id/timers", null],
      ['POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => false]],
    ] as [$method, $uri, $body]) {
      $response = $this->call($method, $uri, $body);
      $this->assertSame(400, $response->getStatusCode(), "$method $uri");
      $this->assertSame($permission, $this->json($response), "$method $uri");
    }

    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft WHERE draft_id = ?', [$id]));
  }

  public function testCompletedDraftsCannotBeUpdated(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Done draft', 'complete');
    $this->logIn('Alice');

    $update = $this->call('PUT', "/commish/draft/$id", $this->newDraftBody(['name' => 'Nope']));
    $this->assertSame(400, $update->getStatusCode());
    $this->assertSame('Done draft', $this->db()->fetchOne('SELECT draft_name FROM draft WHERE draft_id = ?', [$id]));

    $timers = $this->call('POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => false]);
    $this->assertSame(400, $timers->getStatusCode());
  }

  public function testUpdateStatus(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Alice draft');
    $this->logIn('Alice');

    $invalid = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'bogus']);
    $this->assertSame(['Draft status is an invalid value.'], $this->json($invalid)['errors']);

    $complete = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'complete']);
    $this->assertSame(['You cannot set the draft as completed manually.'], $this->json($complete)['errors']);

    $noManagers = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'in_progress']);
    $this->assertSame(['A draft must have at least 2 managers before it can begin.'], $this->json($noManagers)['errors']);

    $this->db()->insert('managers', ['draft_id' => $id, 'manager_name' => 'One', 'draft_order' => 1]);
    $this->db()->insert('managers', ['draft_id' => $id, 'manager_name' => 'Two', 'draft_order' => 2]);

    $start = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'in_progress']);
    $this->assertSame(200, $start->getStatusCode(), $start->getContent());
    $this->assertSame('in_progress', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$id]));
    $this->assertSame(2 * (int) $this->db()->fetchOne('SELECT draft_rounds FROM draft WHERE draft_id = ?', [$id]), (int) $this->db()->fetchOne('SELECT COUNT(*) FROM players WHERE draft_id = ?', [$id]));
  }

  public function testUpdateStatusOnAnInProgressDraft(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Running draft', 'in_progress');
    $this->logIn('Alice');

    $reset = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'undrafted']);
    $this->assertSame(200, $reset->getStatusCode(), $reset->getContent());
    $this->assertSame('undrafted', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$id]));

    $complete = $this->createDraft($alice, 'Finished draft', 'complete');
    $locked = $this->call('PUT', "/commish/draft/$complete/status", ['status' => 'undrafted']);
    $this->assertSame(['The draft is completed, therefore its status cannot be changed.'], $this->json($locked)['errors']);
  }

  public function testTimers(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Alice draft');
    $this->logIn('Alice');

    // Nothing saved yet: one blank, dynamic timer per round
    $blank = $this->json($this->call('GET', "/commish/draft/$id/timers"));
    $this->assertSame([1, 2, 3], array_column($blank, 'draft_round'));
    $this->assertSame([0, 0, 0], array_column($blank, 'round_time_seconds'));

    $dynamic = $this->call('POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => true, 'roundTimes' => [
      ['is_static_time' => 'false', 'draft_round' => 1, 'round_time_seconds' => 30],
      ['is_static_time' => 'false', 'draft_round' => 2, 'round_time_seconds' => 45],
      ['is_static_time' => 'false', 'draft_round' => 3, 'round_time_seconds' => 60],
    ]]);
    $this->assertSame(201, $dynamic->getStatusCode(), $dynamic->getContent());
    $this->assertSame([30, 45, 60], array_map('intval', array_column($this->json($this->call('GET', "/commish/draft/$id/timers")), 'round_time_seconds')));

    $static = $this->call('POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => true, 'roundTimes' => [
      ['is_static_time' => 'true', 'round_time_seconds' => 90],
    ]]);
    $this->assertSame(201, $static->getStatusCode(), $static->getContent());
    $timers = $this->json($this->call('GET', "/commish/draft/$id/timers"));
    $this->assertSame([1, 2, 3], array_column($timers, 'draft_round'));
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM round_times WHERE draft_id = ?', [$id]));

    $invalid = $this->call('POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => true, 'roundTimes' => [
      ['is_static_time' => 'false', 'draft_round' => 40, 'round_time_seconds' => 0],
    ]]);
    $this->assertSame(400, $invalid->getStatusCode());
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM round_times WHERE draft_id = ?', [$id]));

    $disabled = $this->call('POST', "/commish/draft/$id/timers", ['isRoundTimesEnabled' => false]);
    $this->assertSame(201, $disabled->getStatusCode());
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM round_times WHERE draft_id = ?', [$id]));
  }

  public function testSavedTimersShowUnderTheirOwnRounds(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'Alice draft', 'undrafted', ['draft_rounds' => 10]);
    $this->logIn('Alice');

    foreach ([1 => 30, 2 => 45, 5 => 60] as $round => $seconds) {
      $this->db()->insert('round_times', ['draft_id' => $id, 'is_static_time' => 0, 'draft_round' => $round, 'round_time_seconds' => $seconds]);
    }

    $timers = $this->json($this->call('GET', "/commish/draft/$id/timers"));

    $this->assertSame(range(1, 10), array_map('intval', array_column($timers, 'draft_round')));
    $this->assertSame([30, 45, 0, 0, 60, 0, 0, 0, 0, 0], array_map('intval', array_column($timers, 'round_time_seconds')));
  }

  public function testTimeRemaining(): void
  {
    $alice = $this->createUser('Alice');
    $setup = $this->createDraft($alice, 'Setting up');
    $private = $this->createDraft($alice, 'Private draft', 'in_progress', ['draft_password' => 'secret']);
    $started = gmdate('Y-m-d H:i:s', time() - 20);
    $id = $this->createDraft($alice, 'Running draft', 'in_progress', ['draft_start_time' => $started, 'draft_current_round' => 1, 'draft_current_pick' => 1]);
    $this->db()->insert('managers', ['draft_id' => $id, 'manager_name' => 'One', 'draft_order' => 1]);
    $managerId = (int) $this->db()->lastInsertId();
    $this->db()->insert('players', ['draft_id' => $id, 'manager_id' => $managerId, 'player_round' => 1, 'player_pick' => 1]);

    $notRunning = $this->call('GET', "/draft/$setup/timer/remaining");
    $this->assertSame(400, $notRunning->getStatusCode());

    $blocked = $this->call('GET', "/draft/$private/timer/remaining");
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));

    $noTimer = $this->json($this->call('GET', "/draft/$id/timer/remaining"));
    $this->assertTrue($noTimer['success']);
    $this->assertFalse($noTimer['timer_enabled']);

    $this->db()->insert('round_times', ['draft_id' => $id, 'is_static_time' => 1, 'round_time_seconds' => 100]);
    $timer = $this->json($this->call('GET', "/draft/$id/timer/remaining"));
    $this->assertTrue($timer['timer_enabled']);
    $this->assertEqualsWithDelta(80, $timer['seconds_remaining'], 3);

    $this->db()->executeStatement('UPDATE round_times SET round_time_seconds = 10 WHERE draft_id = ?', [$id]);
    $this->assertSame(0, $this->json($this->call('GET', "/draft/$id/timer/remaining"))['seconds_remaining']);
  }

  public function testProfile(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $this->logIn('Alice');

    $this->assertSame(['id' => (string) $alice, 'email' => 'alice@commish-test.example', 'name' => 'Alice'], array_map('strval', $this->json($this->call('GET', '/commish/profile'))));

    $profile = ['_email' => 'alice@commish-test.example', '_name' => 'Alice', '_password' => self::PASSWORD, '_newPassword' => '', '_newConfirmedPassword' => ''];

    $wrong = $this->call('PUT', '/commish/profile', ['_password' => 'nope'] + $profile);
    $this->assertSame(['Incorrect password entered.'], $this->json($wrong)['errors']);

    $taken = $this->call('PUT', '/commish/profile', ['_name' => 'Bob', '_email' => 'bob@commish-test.example', '_newPassword' => 'short', '_newConfirmedPassword' => 'other'] + $profile);
    $this->assertSame(400, $taken->getStatusCode());
    $this->assertEqualsCanonicalizing(['Email already registered.', 'Password is below minimum length.', 'Password values do not match.', 'Name already taken.'], $this->json($taken)['errors']);

    // A name change keeps the login
    $renamed = $this->call('PUT', '/commish/profile', ['_name' => 'Alicia'] + $profile);
    $this->assertSame(200, $renamed->getStatusCode(), $renamed->getContent());
    $this->assertFalse($this->json($renamed)['invalidateLogin']);
    $this->assertSame('Alicia', $this->db()->fetchOne('SELECT name FROM users WHERE id = ?', [$alice]));
    $this->assertSame(200, $this->call('GET', '/commish/profile')->getStatusCode());

    // A new password logs them out, rehashed with the modern algorithm
    $changed = $this->call('PUT', '/commish/profile', ['_name' => 'Alicia', '_newPassword' => 'a brand new one', '_newConfirmedPassword' => 'a brand new one'] + $profile);
    $this->assertSame(200, $changed->getStatusCode(), $changed->getContent());
    $this->assertTrue($this->json($changed)['invalidateLogin']);
    $this->assertSame('', $this->db()->fetchOne('SELECT salt FROM users WHERE id = ?', [$alice]));
    $this->assertSame(401, $this->call('GET', '/commish/profile')->getStatusCode());
    $this->assertSame(400, $this->logIn('Alice')->getStatusCode());
    $this->assertSame(200, $this->logIn('Alice', 'a brand new one')->getStatusCode());
  }

  public function testNameCanChangeOnlyInCapitalization(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');
    $profile = ['_email' => 'alice@commish-test.example', '_password' => self::PASSWORD, '_newPassword' => '', '_newConfirmedPassword' => ''];

    $recased = $this->call('PUT', '/commish/profile', ['_name' => 'ALICE'] + $profile);

    $this->assertSame(200, $recased->getStatusCode(), $recased->getContent());
    $this->assertSame('ALICE', $this->db()->fetchOne('SELECT name FROM users WHERE id = ?', [$alice]));
  }

  public function testChangingTheEmailRequiresVerificationAgain(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');

    $response = $this->call('PUT', '/commish/profile', ['_email' => 'ALICE2@commish-test.example', '_name' => 'Alice', '_password' => self::PASSWORD, '_newPassword' => '', '_newConfirmedPassword' => '']);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);
    $this->assertTrue($body['invalidateLogin']);
    $this->assertTrue($body['sendEmail']);

    $row = $this->db()->fetchAssociative('SELECT email, enabled, verificationKey FROM users WHERE id = ?', [$alice]);
    $this->assertSame('alice2@commish-test.example', $row['email']);
    $this->assertSame(0, (int) $row['enabled']);
    $this->assertNotEmpty($row['verificationKey']);

    $mailer = static::getContainer()->get(EmailService::class);
    $this->assertInstanceOf(CapturingEmailService::class, $mailer);
    $this->assertCount(1, $mailer->sent);
    $this->assertSame(['alice2@commish-test.example' => 'Alice'], $mailer->sent[0]->to_addresses);

    // The session is gone, and the address verifies with the emailed link
    $this->assertSame(401, $this->call('GET', '/commish/profile')->getStatusCode());
    $verified = $this->call('POST', '/verify', ['_email' => 'alice2@commish-test.example', '_verificationToken' => $row['verificationKey']]);
    $this->assertSame(200, $verified->getStatusCode(), $verified->getContent());
  }

  public function testInviteNewCommissioner(): void
  {
    $this->createUser('Alice');
    $this->logIn('Alice');

    $invalid = $this->call('POST', '/commish/user/invite', ['email' => 'not-an-email', 'name' => 'Alice', 'message' => str_repeat('x', 256)]);
    $this->assertSame(400, $invalid->getStatusCode());
    $this->assertEqualsCanonicalizing(['Email invalid.', 'Message too long', 'Name already taken.'], $this->json($invalid)['errors']);

    $invited = $this->call('POST', '/commish/user/invite', ['email' => 'Carol@commish-test.example', 'name' => 'Carol', 'message' => 'Join us']);
    $this->assertSame(200, $invited->getStatusCode(), $invited->getContent());
    $this->assertTrue($this->json($invited)['success']);

    $mailer = static::getContainer()->get(EmailService::class);
    $this->assertCount(1, $mailer->sent);
    $this->assertSame('Alice invited you to use Hoot Draft!', $mailer->sent[0]->subject);
    $this->assertSame(['carol@commish-test.example' => 'Carol'], $mailer->sent[0]->to_addresses);

    $row = $this->db()->fetchAssociative("SELECT * FROM users WHERE email = 'carol@commish-test.example'");
    $this->assertSame('ROLE_COMMISH', $row['roles']);
    $this->assertSame(1, (int) $row['enabled']);

    // Nobody knows the generated password
    $this->assertSame(400, $this->logIn('Carol')->getStatusCode());

    // The invitation link goes through the reset endpoints, and stays good for longer than a reset link
    $this->cookies = [];
    $token = $row['verificationKey'];
    $this->assertSame(200, $this->call('GET', '/verifyToken?' . http_build_query(['_email' => 'carol@commish-test.example', '_verificationToken' => $token]))->getStatusCode());

    $reset = $this->call('POST', '/resetPassword', ['_email' => 'carol@commish-test.example', '_password' => 'carols password', '_confirmPassword' => 'carols password', '_verificationToken' => $token]);
    $this->assertSame(200, $reset->getStatusCode(), $reset->getContent());
    $this->assertSame('Carol', $this->json($reset)['name']);
    $this->assertSame(200, $this->logIn('Carol', 'carols password')->getStatusCode());

    $again = $this->call('POST', '/commish/user/invite', ['email' => 'carol@commish-test.example', 'name' => 'Someone Else']);
    $this->assertSame(['Email already registered.'], $this->json($again)['errors']);
  }
}
