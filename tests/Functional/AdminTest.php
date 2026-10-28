<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/** The admin routes (/admin/*): drafts and stats, the pro player CSV upload, and user management. */
#[Group('database')]
class AdminTest extends CommishDraftTestCase
{
  private array $tempFiles = [];

  protected static function domain(): string
  {
    return 'admin-test.example';
  }

  protected function setUp(): void
  {
    parent::setUp();
    $this->db()->executeStatement("DELETE FROM pro_players WHERE league = 'NHL'");
  }

  protected function tearDown(): void
  {
    $this->db()->executeStatement("DELETE FROM pro_players WHERE league = 'NHL'");
    array_map('unlink', array_filter($this->tempFiles, 'file_exists'));
    parent::tearDown();
  }

  private function csv(string $contents): UploadedFile
  {
    $path = tempnam(sys_get_temp_dir(), 'hdcsv');
    file_put_contents($path, $contents);
    $this->tempFiles[] = $path;

    return new UploadedFile($path, 'players.csv', 'text/csv', null, true);
  }

  private function logInAsAdmin(): void
  {
    $this->createUser('Root', 'ROLE_ADMIN');
    $this->logIn('Root');
  }

  public function testEveryAdminRouteNeedsAnAdminSession(): void
  {
    $routes = [['GET', '/admin/drafts'], ['POST', '/admin/draft/1/stats'], ['GET', '/admin/sports'], ['POST', '/admin/proplayers'],
      ['GET', '/admin/users'], ['PUT', '/admin/user/1'], ['DELETE', '/admin/user/1']];

    foreach ($routes as [$method, $uri]) {
      $this->assertSame(401, $this->call($method, $uri)->getStatusCode(), "$method $uri");
    }

    $this->createUser('Alice');
    $this->logIn('Alice');
    foreach ($routes as [$method, $uri]) {
      $this->assertSame(403, $this->call($method, $uri)->getStatusCode(), "$method $uri");
    }
  }

  public function testSportsAndCompletedDrafts(): void
  {
    [$complete] = $this->seedDraft('complete');
    $this->seedDraft('in_progress');
    $this->logInAsAdmin();

    $sports = $this->json($this->call('GET', '/admin/sports'));
    $this->assertSame('Football (NFL)', $sports['NFL'] ?? null);

    $drafts = $this->json($this->call('GET', '/admin/drafts'));
    $ids = array_map('intval', array_column($drafts, 'draft_id'));
    $this->assertContains($complete, $ids);
    $this->assertCount(1, array_filter($drafts, fn ($d) => $d['commish_name'] === 'Alice' && $d['draft_status'] === 'complete'));
  }

  public function testRegeneratingStatsReplacesTheOldRow(): void
  {
    [$draft, $a, $b] = $this->seedDraft('complete');
    $this->db()->update('players', ['first_name' => 'X', 'last_name' => 'Y', 'team' => 'ATL', 'position' => 'QB', 'pick_time' => '2024-01-01 12:05:00', 'pick_duration' => 60, 'player_counter' => 1], ['draft_id' => $draft, 'manager_id' => $a]);
    $this->db()->update('draft', ['draft_end_time' => '2024-01-01 13:00:00'], ['draft_id' => $draft]);
    $this->logInAsAdmin();

    $first = $this->call('POST', "/admin/draft/$draft/stats");
    $this->assertSame(200, $first->getStatusCode(), $first->getContent());
    $stats = $this->json($first);
    $this->assertSame(3600, $stats['drafting_time_seconds']);
    $this->assertSame('Atlanta Falcons', $stats['most_drafted_team']);

    $this->call('POST', "/admin/draft/$draft/stats");
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft_stats WHERE draft_id = ?', [$draft]));
    $this->assertNotNull($this->db()->fetchOne('SELECT draft_stats_generated FROM draft WHERE draft_id = ?', [$draft]));

    $this->assertSame(404, $this->call('POST', '/admin/draft/999999999/stats')->getStatusCode());
  }

  public function testPicksWithoutADurationDoNotCountAsTheFastest(): void
  {
    [$draft, $a, $b] = $this->seedDraft('complete');
    $this->db()->update('draft', ['draft_end_time' => '2024-01-01 13:00:00'], ['draft_id' => $draft]);
    $this->db()->executeStatement('UPDATE players SET pick_duration = 60 WHERE draft_id = ? AND manager_id = ? AND player_round = 1', [$draft, $a]);
    $this->db()->executeStatement('UPDATE players SET pick_duration = 120 WHERE draft_id = ? AND manager_id = ? AND player_round = 2', [$draft, $a]);
    $this->db()->executeStatement('UPDATE players SET pick_duration = NULL WHERE draft_id = ? AND manager_id = ?', [$draft, $b]);
    $this->logInAsAdmin();

    $stats = $this->json($this->call('POST', "/admin/draft/$draft/stats"));

    $this->assertSame('Team A', $stats['shortest_single_pick_manager_name']);
    $this->assertSame(60, $stats['shortest_single_pick_seconds']);
    $this->assertSame('Team A', $stats['shortest_avg_pick_manager_name']);
    $this->assertSame(90, $stats['shortest_avg_pick_seconds']);
  }

  public function testStatsCannotBeCalculatedForAnUnfinishedDraft(): void
  {
    [$draft] = $this->seedDraft('in_progress');
    $this->logInAsAdmin();

    $this->assertSame(409, $this->call('POST', "/admin/draft/$draft/stats")->getStatusCode());
    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft_stats WHERE draft_id = ?', [$draft]));
  }

  public function testUploadReplacesTheLeaguesPlayers(): void
  {
    $this->logInAsAdmin();
    $this->db()->insert('pro_players', ['league' => 'NHL', 'first_name' => 'Old', 'last_name' => 'Timer', 'position' => 'C', 'team' => 'BOS']);

    $csv = "\xEF\xBB\xBFPlayer;Position;Team\n\"Crosby, Sidney\";C;PIT\n\n\"Ovechkin, Alex\" ; LW ; WAS\n\"Smith Jr, John, III\";D;NYR\n";
    $response = $this->upload('/admin/proplayers', ['sport' => 'NHL'], ['file' => $this->csv($csv)]);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertTrue($this->json($response)['success']);

    $rows = $this->db()->fetchAllAssociative("SELECT first_name, last_name, position, team FROM pro_players WHERE league = 'NHL' ORDER BY pro_player_id");
    $this->assertSame([
      ['first_name' => 'Sidney', 'last_name' => 'Crosby', 'position' => 'C', 'team' => 'PIT'],
      ['first_name' => 'Alex', 'last_name' => 'Ovechkin', 'position' => 'LW', 'team' => 'WAS'],
      ['first_name' => 'John, III', 'last_name' => 'Smith Jr', 'position' => 'D', 'team' => 'NYR'],
    ], $rows);
  }

  public function testUploadConvertsWindows1252ToUtf8(): void
  {
    $this->logInAsAdmin();

    $csv = "Player;Position;Team\n" . mb_convert_encoding("Mu\u{00F1}oz, Andr\u{00E9}s", 'Windows-1252', 'UTF-8') . ";C;PIT\n";
    $response = $this->upload('/admin/proplayers', ['sport' => 'NHL'], ['file' => $this->csv($csv)]);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    $row = $this->db()->fetchAssociative("SELECT first_name, last_name FROM pro_players WHERE league = 'NHL'");
    $this->assertSame(['first_name' => "Andr\u{00E9}s", 'last_name' => "Mu\u{00F1}oz"], $row);
  }

  public function testUploadDatabaseFailureKeepsTheOldPlayersAndHidesTheError(): void
  {
    $this->logInAsAdmin();
    $this->db()->insert('pro_players', ['league' => 'NHL', 'first_name' => 'Old', 'last_name' => 'Timer', 'position' => 'C', 'team' => 'BOS']);

    $this->db()->executeStatement('CREATE TRIGGER admin_test_block_players BEFORE INSERT ON pro_players FOR EACH ROW SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'secret table detail\'');
    try {
      $response = $this->upload('/admin/proplayers', ['sport' => 'NHL'], ['file' => $this->csv("\"Crosby, Sidney\";C;PIT\n")]);
    } finally {
      $this->db()->executeStatement('DROP TRIGGER IF EXISTS admin_test_block_players');
    }

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['Error encountered when updating new players to database.'], $this->json($response)['errors']);
    $this->assertSame(1, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM pro_players WHERE league = 'NHL' AND last_name = 'Timer'"));
  }

  public function testUploadWithMalformedRowsKeepsTheOldPlayers(): void
  {
    $this->logInAsAdmin();
    $this->db()->insert('pro_players', ['league' => 'NHL', 'first_name' => 'Old', 'last_name' => 'Timer', 'position' => 'C', 'team' => 'BOS']);

    $response = $this->upload('/admin/proplayers', ['sport' => 'NHL'], ['file' => $this->csv("Player;Position;Team\n\"Crosby, Sidney\";C;PIT\nBroken Row;C;PIT\n")]);
    $this->assertSame(400, $response->getStatusCode(), $response->getContent());
    $this->assertStringContainsString('Rows 3 ', $this->json($response)['errors'][0]);
    $this->assertSame(1, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM pro_players WHERE league = 'NHL' AND last_name = 'Timer'"));
  }

  public function testUploadValidationFailuresChangeNothing(): void
  {
    $this->logInAsAdmin();
    $this->db()->insert('pro_players', ['league' => 'NHL', 'first_name' => 'Old', 'last_name' => 'Timer', 'position' => 'C', 'team' => 'BOS']);

    $noFile = $this->upload('/admin/proplayers', ['sport' => 'NHL'], []);
    $this->assertSame(400, $noFile->getStatusCode());
    $this->assertContains('Must upload a CSV file', $this->json($noFile)['errors']);

    $badSport = $this->upload('/admin/proplayers', ['sport' => 'QVC'], ['file' => $this->csv("\"A, B\";C;D\n")]);
    $this->assertSame(400, $badSport->getStatusCode());
    $this->assertContains('Sport QVC is an invalid value.', $this->json($badSport)['errors']);

    $empty = $this->upload('/admin/proplayers', ['sport' => 'NHL'], ['file' => $this->csv("Player;Position;Team\n\n")]);
    $this->assertSame(400, $empty->getStatusCode());
    $this->assertSame(1, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM pro_players WHERE league = 'NHL' AND last_name = 'Timer'"));
  }

  public function testUserListHidesSecrets(): void
  {
    $this->createUser('Alice');
    $this->logInAsAdmin();

    $body = $this->json($this->call('GET', '/admin/users'));

    $this->assertTrue($body['success']);
    $this->assertSame(['ROLE_COMMISH' => 'Commissioner', 'ROLE_ADMIN' => 'Administrator'], $body['roles']);
    $alice = array_values(array_filter($body['users'], fn ($u) => $u['email'] === 'alice@' . static::domain()))[0];
    $this->assertSame(['id', 'enabled', 'email', 'name', 'roles', 'creationTime'], array_keys($alice));
  }

  public function testUpdateUser(): void
  {
    $alice = $this->createUser('Alice');
    $this->logInAsAdmin();

    $response = $this->call('PUT', "/admin/user/$alice", ['email' => 'Alice2@admin-test.example', 'name' => 'Alice Two', 'roles' => 'ROLE_ADMIN', 'enabled' => 0]);
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertSame(['success' => true, 'errors' => []], $this->json($response));

    $row = $this->db()->fetchAssociative('SELECT * FROM users WHERE id = ?', [$alice]);
    $this->assertSame('alice2@admin-test.example', $row['email']);
    $this->assertSame('Alice Two', $row['name']);
    $this->assertSame('ROLE_ADMIN', $row['roles']);
    $this->assertSame(0, (int) $row['enabled']);
    $this->assertNotEmpty($row['password'], 'the password hash is left alone');
  }

  public function testUpdateUserValidation(): void
  {
    $alice = $this->createUser('Alice');
    $this->createUser('Bob');
    $this->logInAsAdmin();

    $response = $this->call('PUT', "/admin/user/$alice", ['email' => 'bob@admin-test.example', 'name' => 'Bob', 'roles' => 'ROLE_SUPER', 'enabled' => 1]);
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['Email already registered.', 'Name already taken.', 'Role invalid.'], $this->json($response)['errors']);

    $this->assertSame(400, $this->call('PUT', "/admin/user/$alice", ['email' => '', 'name' => 'Alice', 'roles' => 'ROLE_COMMISH'])->getStatusCode());
    $this->assertSame(400, $this->call('PUT', "/admin/user/$alice", ['email' => 'alice@admin-test.example', 'name' => '', 'roles' => 'ROLE_COMMISH'])->getStatusCode());
    $this->assertSame(400, $this->call('PUT', "/admin/user/$alice", ['email' => ['a'], 'name' => 'Alice', 'roles' => 'ROLE_COMMISH'])->getStatusCode());
    $this->assertSame(400, $this->call('PUT', "/admin/user/$alice", ['email' => 'alice@admin-test.example', 'name' => 'Alice', 'roles' => [['ROLE_COMMISH']]])->getStatusCode());
    $this->assertSame(404, $this->call('PUT', '/admin/user/999999999', ['email' => 'a@b.co', 'name' => 'Z', 'roles' => 'ROLE_COMMISH'])->getStatusCode());
    $this->assertSame('alice@admin-test.example', $this->db()->fetchOne('SELECT email FROM users WHERE id = ?', [$alice]));
  }

  public function testUpdateUserWithoutEnabledIsRejected(): void
  {
    $alice = $this->createUser('Alice');
    $this->logInAsAdmin();

    $response = $this->call('PUT', "/admin/user/$alice", ['email' => 'alice@admin-test.example', 'name' => 'Alice', 'roles' => 'ROLE_COMMISH']);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['Invalid user fields.'], $this->json($response)['errors']);
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT enabled FROM users WHERE id = ?', [$alice]));
  }

  public function testAnAdminCannotRemoveTheirOwnAccess(): void
  {
    $this->logInAsAdmin();
    $root = (int) $this->db()->fetchOne('SELECT id FROM users WHERE email = ?', ['root@admin-test.example']);
    $self = ['email' => 'root@admin-test.example', 'name' => 'Root', 'roles' => 'ROLE_ADMIN', 'enabled' => 1];

    foreach ([['roles' => 'ROLE_COMMISH'], ['enabled' => 0]] as $change) {
      $response = $this->call('PUT', "/admin/user/$root", $change + $self);
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame(["You can't remove your own admin role or disable your own account."], $this->json($response)['errors']);
    }

    $this->assertSame(200, $this->call('PUT', "/admin/user/$root", ['name' => 'Root Renamed'] + $self)->getStatusCode());
    $this->assertSame('ROLE_ADMIN', $this->db()->fetchOne('SELECT roles FROM users WHERE id = ?', [$root]));
  }

  public function testDeleteUserRemovesTheirDraftsToo(): void
  {
    [$draft, $a] = $this->seedDraft('in_progress');
    $alice = (int) $this->db()->fetchOne('SELECT commish_id FROM draft WHERE draft_id = ?', [$draft]);
    $this->createPick($draft, $a, 3, 1);
    $other = $this->createDraft($this->createUser('Bob'));
    $this->logInAsAdmin();

    $response = $this->call('DELETE', "/admin/user/$alice");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertTrue($this->json($response)['success']);

    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM users WHERE id = ?', [$alice]));
    foreach (['draft', 'managers', 'players'] as $table) {
      $this->assertSame(0, (int) $this->db()->fetchOne("SELECT COUNT(*) FROM $table WHERE draft_id = ?", [$draft]), $table);
    }
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft WHERE draft_id = ?', [$other]), "Bob's draft is untouched");
  }

  public function testDeleteUserIsAllOrNothing(): void
  {
    [$draft] = $this->seedDraft('in_progress');
    $alice = (int) $this->db()->fetchOne('SELECT commish_id FROM draft WHERE draft_id = ?', [$draft]);
    $second = $this->createDraft($alice);
    $this->logInAsAdmin();

    // Make the second delete fail after the first draft was already removed in the same transaction
    $this->db()->executeStatement('CREATE TRIGGER admin_test_block BEFORE DELETE ON draft FOR EACH ROW BEGIN IF OLD.draft_id = ' . $second . ' THEN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'blocked\'; END IF; END');
    try {
      $response = $this->call('DELETE', "/admin/user/$alice");
    } finally {
      $this->db()->executeStatement('DROP TRIGGER IF EXISTS admin_test_block');
    }

    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->json($response)['success']);
    $this->assertSame(1, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM users WHERE id = ?', [$alice]));
    $this->assertSame(2, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM draft WHERE commish_id = ?', [$alice]));
    $this->assertSame(2, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM managers WHERE draft_id = ?', [$draft]));
    $this->assertSame(4, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM players WHERE draft_id = ?', [$draft]));
  }

  public function testAdminsCannotBeDeleted(): void
  {
    $this->logInAsAdmin();
    $other = $this->createUser('Boss', 'ROLE_COMMISH,ROLE_ADMIN');

    $response = $this->call('DELETE', "/admin/user/$other");
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['Unable to delete user - user is admin.'], $this->json($response)['errors']);
    $this->assertSame(404, $this->call('DELETE', '/admin/user/999999999')->getStatusCode());
  }
}
