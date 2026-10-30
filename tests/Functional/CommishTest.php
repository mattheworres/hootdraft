<?php

namespace HootDraft\Tests\Functional;

use HootDraft\Domain\Services\EmailService;
use HootDraft\Tests\Support\CapturingEmailService;
use PHPUnit\Framework\Attributes\Group;

/** The commissioner's drafts, round timers, profile and invitations, plus the public pick timer, against the test DB. */
#[Group('database')]
class CommishTest extends CommishDraftTestCase
{
  protected bool $keepKernelBetweenCalls = true;

  protected static function domain(): string
  {
    return 'commish-test.example';
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

    $running = $this->createDraft($alice, 'in_progress', null, ['using_depth_charts' => 1]);
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
    $setup = $this->createDraft($alice, 'undrafted', null, ['using_depth_charts' => 1]);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $setup, 'position' => 'QB', 'slots' => 1, 'display_order' => 0]);

    $off = $this->call('PUT', "/commish/draft/$setup", $this->newDraftBody(['name' => 'Setup draft', 'using_depth_charts' => false]));
    $this->assertSame(200, $off->getStatusCode(), $off->getContent());
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM depth_chart_positions WHERE draft_id = ?', [$setup]));
  }

  public function testAnotherCommissionerCannotTouchTheDraft(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $id = $this->createDraft($alice, 'undrafted');
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
    $id = $this->createDraft($alice, 'complete', null, ['draft_name' => 'Done draft']);
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
    $id = $this->createDraft($alice, 'undrafted');
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
    $id = $this->createDraft($alice, 'in_progress');
    $this->logIn('Alice');

    $reset = $this->call('PUT', "/commish/draft/$id/status", ['status' => 'undrafted']);
    $this->assertSame(200, $reset->getStatusCode(), $reset->getContent());
    $this->assertSame('undrafted', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$id]));

    $complete = $this->createDraft($alice, 'complete');
    $locked = $this->call('PUT', "/commish/draft/$complete/status", ['status' => 'undrafted']);
    $this->assertSame(['The draft is completed, therefore its status cannot be changed.'], $this->json($locked)['errors']);
  }

  public function testTimers(): void
  {
    $alice = $this->createUser('Alice');
    $id = $this->createDraft($alice, 'undrafted', null, ['draft_rounds' => 3]);
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
    $id = $this->createDraft($alice, 'undrafted', null, ['draft_rounds' => 10]);
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
    $setup = $this->createDraft($alice, 'undrafted');
    $private = $this->createDraft($alice, 'in_progress', null, ['draft_password' => 'secret']);
    $started = gmdate('Y-m-d H:i:s', time() - 20);
    $id = $this->createDraft($alice, 'in_progress', null, ['draft_start_time' => $started, 'draft_current_round' => 1, 'draft_current_pick' => 1]);
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

  public function testChangingTheEmailRequiresEmailVerificationAgain(): void
  {
    $alice = $this->createUser('Alice');
    $this->logIn('Alice');

    $response = $this->call('PUT', '/commish/profile', ['_email' => 'ALICE2@commish-test.example', '_name' => 'Alice', '_password' => self::PASSWORD, '_newPassword' => '', '_newConfirmedPassword' => '']);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);
    $this->assertTrue($body['invalidateLogin']);
    $this->assertTrue($body['sendEmail']);

    $row = $this->db()->fetchAssociative('SELECT email, enabled, emailVerified, emailVerificationKey, resetKey FROM users WHERE id = ?', [$alice]);
    $this->assertSame('alice2@commish-test.example', $row['email']);
    $this->assertSame([1, 0], [(int) $row['enabled'], (int) $row['emailVerified']], 'only an Admin writes enabled');
    $this->assertSame(16, strlen($row['emailVerificationKey']));
    $this->assertNull($row['resetKey']);

    $mailer = static::getContainer()->get(EmailService::class);
    $this->assertInstanceOf(CapturingEmailService::class, $mailer);
    $this->assertCount(1, $mailer->sent);
    $this->assertSame(['alice2@commish-test.example' => 'Alice'], $mailer->sent[0]->to_addresses);

    // The session is gone, and the address becomes Email Verified with the emailed link
    $this->assertSame(401, $this->call('GET', '/commish/profile')->getStatusCode());
    $followed = $this->call('POST', '/emailVerification', ['_email' => 'alice2@commish-test.example', '_emailVerificationKey' => $row['emailVerificationKey']]);
    $this->assertSame(200, $followed->getStatusCode(), $followed->getContent());
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT emailVerified FROM users WHERE id = ?', [$alice]));
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
    $this->assertSame([1, 1], [(int) $row['enabled'], (int) $row['emailVerified']]);

    // Nobody knows the generated password
    $this->assertSame(400, $this->logIn('Carol')->getStatusCode());

    // The invitation link goes through the reset endpoints, and stays good for longer than a reset link
    $this->cookies = [];
    $token = $row['resetKey'];
    $this->assertSame(200, $this->call('GET', '/checkResetKey?' . http_build_query(['_email' => 'carol@commish-test.example', '_resetKey' => $token]))->getStatusCode());

    $reset = $this->call('POST', '/resetPassword', ['_email' => 'carol@commish-test.example', '_password' => 'carols password', '_confirmPassword' => 'carols password', '_resetKey' => $token]);
    $this->assertSame(200, $reset->getStatusCode(), $reset->getContent());
    $this->assertSame('Carol', $this->json($reset)['name']);
    $this->assertSame(200, $this->logIn('Carol', 'carols password')->getStatusCode());

    $again = $this->call('POST', '/commish/user/invite', ['email' => 'carol@commish-test.example', 'name' => 'Someone Else']);
    $this->assertSame(['Email already registered.'], $this->json($again)['errors']);
  }
}
