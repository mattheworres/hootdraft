<?php

namespace HootDraft\Tests\Functional;

use Doctrine\DBAL\Connection;
use HootDraft\Domain\Services\DatabaseCacheService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Public pick reads, search and the depth chart (read and update), against the migrated test DB. */
#[Group('database')]
class PickTest extends KernelTestCase
{
  private const PASSWORD_HEADER = 'HTTP_X_PHPDRAFT_DRAFTPASSWORD';
  private const EMAIL = 'alice@pick-test.example';

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
    $ids = $this->db()->fetchFirstColumn("SELECT draft_id FROM draft WHERE commish_id IN (SELECT id FROM users WHERE email LIKE '%@pick-test.example')");
    foreach ($ids as $id) {
      $this->db()->executeStatement('DELETE FROM players WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM managers WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM depth_chart_positions WHERE draft_id = ?', [$id]);
      $this->db()->executeStatement('DELETE FROM draft WHERE draft_id = ?', [$id]);
    }
    $this->db()->executeStatement("DELETE FROM users WHERE email LIKE '%@pick-test.example'");

    $cache = static::getContainer()->get(DatabaseCacheService::class);
    foreach (array_merge($this->draftIds, $ids) as $id) {
      $cache->DeleteCachedItem("draft$id");
    }
    $this->draftIds = [];
  }

  private function db(): Connection
  {
    return static::getContainer()->get(Connection::class);
  }

  private function createUser(): int
  {
    $this->db()->insert('users', [
      'email' => self::EMAIL, 'password' => 'x', 'salt' => 'x', 'name' => 'Alice',
      'roles' => 'ROLE_COMMISH', 'enabled' => 1, 'creationTime' => '2020-01-01 00:00:00',
    ]);

    return (int) $this->db()->lastInsertId();
  }

  private function createDraft(int $commishId, string $status = 'in_progress', ?string $password = null, int $depthCharts = 0, ?string $endTime = null): int
  {
    $this->db()->insert('draft', [
      'commish_id' => $commishId, 'draft_create_time' => '2024-01-01 12:00:00', 'draft_name' => 'Pick test draft', 'draft_sport' => 'NFL',
      'draft_status' => $status, 'draft_style' => 'standard', 'draft_rounds' => 2, 'draft_password' => $password,
      'draft_current_round' => 2, 'draft_current_pick' => 3, 'draft_counter' => 2, 'using_depth_charts' => $depthCharts,
      'draft_start_time' => '2024-01-01 12:00:00', 'draft_end_time' => $endTime,
    ]);
    $id = (int) $this->db()->lastInsertId();
    $this->draftIds[] = $id;

    return $id;
  }

  private function createManager(int $draftId, string $name, int $order): int
  {
    $this->db()->insert('managers', ['draft_id' => $draftId, 'manager_name' => $name, 'draft_order' => $order]);

    return (int) $this->db()->lastInsertId();
  }

  private function createPick(int $draftId, int $managerId, int $round, int $pick, ?array $player = null, ?int $counter = null): int
  {
    $row = ['draft_id' => $draftId, 'manager_id' => $managerId, 'player_round' => $round, 'player_pick' => $pick];
    if ($player) {
      $row += $player + ['pick_time' => '2024-01-01 12:05:00', 'pick_duration' => 300, 'player_counter' => $counter];
    }
    $this->db()->insert('players', $row);

    return (int) $this->db()->lastInsertId();
  }

  /** A second, private draft owned by the same commissioner. Returns [draft, manager A, manager B, pick ids]. */
  private function seedPrivateDraft(): array
  {
    $alice = (int) $this->db()->fetchOne('SELECT id FROM users WHERE email = ?', [self::EMAIL]);
    $draft = $this->createDraft($alice, 'in_progress', 'secret');
    $a = $this->createManager($draft, 'Private A', 1);
    $b = $this->createManager($draft, 'Private B', 2);
    $picks = [
      $this->createPick($draft, $a, 1, 1, ['first_name' => 'Secret', 'last_name' => 'Player', 'team' => 'PIT', 'position' => 'WR'], 1),
      $this->createPick($draft, $b, 1, 2),
    ];

    return [$draft, $a, $b, $picks];
  }

  /** Two managers and four picks (round 1: A, B; round 2: B, A); the first two are made. Returns [draft, A, B, pick ids]. */
  private function seedDraft(string $status = 'in_progress', ?string $password = null, int $depthCharts = 0, ?string $endTime = null): array
  {
    $alice = $this->createUser();
    $draft = $this->createDraft($alice, $status, $password, $depthCharts, $endTime);
    $a = $this->createManager($draft, 'Team A', 1);
    $b = $this->createManager($draft, 'Team B', 2);
    $picks = [
      $this->createPick($draft, $a, 1, 1, ['first_name' => 'Antonio', 'last_name' => 'Brown', 'team' => 'PIT', 'position' => 'WR'], 1),
      $this->createPick($draft, $b, 1, 2, ['first_name' => 'Julio', 'last_name' => 'Jones', 'team' => 'ATL', 'position' => 'WR'], 2),
      $this->createPick($draft, $b, 2, 3),
      $this->createPick($draft, $a, 2, 4),
    ];

    return [$draft, $a, $b, $picks];
  }

  private function call(string $method, string $uri, ?array $body = null, array $headers = []): Response
  {
    $server = $headers + ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
    $request = Request::create($uri, $method, [], $this->cookies, [], $server, $body === null ? null : json_encode($body));
    $response = self::$kernel->handle($request);

    foreach ($response->headers->getCookies() as $cookie) {
      $this->cookies[$cookie->getName()] = $cookie->getValue();
    }

    self::$kernel->shutdown();
    self::bootKernel();

    return $response;
  }

  private function json(Response $response): mixed
  {
    return json_decode($response->getContent(), true);
  }

  public function testPicksAreHiddenUntilTheDraftHasStarted(): void
  {
    [$draft] = $this->seedDraft('undrafted');

    $response = $this->call('GET', "/draft/$draft/picks");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->json($response)[0]['success']);
  }

  public function testPrivateDraftNeedsThePasswordHeader(): void
  {
    [$draft] = $this->seedDraft('in_progress', 'secret');

    $blocked = $this->call('GET', "/draft/$draft/picks/last");
    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));

    $unlocked = $this->call('GET', "/draft/$draft/picks/last", null, [self::PASSWORD_HEADER => 'secret']);
    $this->assertSame(200, $unlocked->getStatusCode());
  }

  public function testGetAllGroupsPicksByRound(): void
  {
    [$draft] = $this->seedDraft();

    $response = $this->call('GET', "/draft/$draft/picks");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertCount(2, $body['allPicks']);
    $this->assertSame([1, 2], array_map('intval', array_column($body['allPicks'][0], 'player_pick')));
    $this->assertSame([3, 4], array_map('intval', array_column($body['allPicks'][1], 'player_pick')));
    $this->assertTrue($body['allPicks'][0][0]['selected']);
    $this->assertFalse($body['allPicks'][1][0]['selected']);
    $this->assertTrue($body['allPicks'][1][0]['on_the_clock']);
    $this->assertSame('Team A', $body['allPicks'][0][0]['manager_name']);
  }

  public function testLastAndNextPicks(): void
  {
    [$draft] = $this->seedDraft();

    $last = $this->json($this->call('GET', "/draft/$draft/picks/last"));
    $this->assertSame(['Julio', 'Antonio'], array_column($last, 'first_name'));
    $this->assertCount(1, $this->json($this->call('GET', "/draft/$draft/picks/last?amount=1")));

    foreach (['-5', '0', 'abc', '1000000000'] as $amount) {
      $response = $this->call('GET', "/draft/$draft/picks/last?amount=$amount");
      $this->assertSame(200, $response->getStatusCode(), "amount=$amount");
      $this->assertCount(2, $this->json($response), "amount=$amount");

      $this->assertSame(200, $this->call('GET', "/draft/$draft/picks/next?amount=$amount")->getStatusCode(), "amount=$amount");
    }

    $next = $this->json($this->call('GET', "/draft/$draft/picks/next"));
    $this->assertSame([3, 4], array_map('intval', array_column($next, 'player_pick')));
    $this->assertTrue($next[0]['on_the_clock']);
    $this->assertFalse($next[1]['on_the_clock']);
  }

  public function testUpdatedPicksOnlyWhenTheCounterIsBehind(): void
  {
    [$draft] = $this->seedDraft();

    $behind = $this->json($this->call('GET', "/draft/$draft/picks/updated?pick_counter=1"));
    $this->assertTrue($behind['success'], json_encode($behind));
    $this->assertSame(['Julio'], array_column($behind['updated_picks'], 'first_name'));
    $this->assertSame(3, (int) $behind['current_pick']['player_pick']);
    $this->assertSame(2, (int) $behind['previous_pick']['player_pick']);
    $this->assertFalse($behind['timer_enabled']);
    $this->assertEquals(2, $behind['draft_counter']);

    $current = $this->json($this->call('GET', "/draft/$draft/picks/updated?pick_counter=2"));
    $this->assertTrue($current['success']);
    $this->assertArrayNotHasKey('updated_picks', $current);
  }

  public function testManagerAndRoundPicks(): void
  {
    [$draft, $a, $b] = $this->seedDraft();

    $all = $this->json($this->call('GET', "/draft/$draft/manager/$a/picks/all"));
    $this->assertSame([1, 4], array_column($all, 'player_pick'));
    $this->assertSame([false, false], array_column($all, 'on_the_clock'));

    $selected = $this->json($this->call('GET', "/draft/$draft/manager/$b/picks/selected"));
    $this->assertSame([2], array_column($selected, 'player_pick'));

    $round = $this->json($this->call('GET', "/draft/$draft/round/2/picks/all?sort_ascending=1"));
    $this->assertSame([3, 4], array_map('intval', array_column($round, 'player_pick')));
    $reversed = $this->json($this->call('GET', "/draft/$draft/round/2/picks/all"));
    $this->assertSame([4, 3], array_map('intval', array_column($reversed, 'player_pick')));

    $this->assertSame([], $this->json($this->call('GET', "/draft/$draft/round/2/picks/selected?sort_ascending=1")));
    $this->assertSame([1, 2], array_map('intval', array_column($this->json($this->call('GET', "/draft/$draft/round/1/picks/selected?sort_ascending=1")), 'player_pick')));
  }

  public function testManagerRoutesDoNotLeakPicksFromAnotherDraft(): void
  {
    [$draft] = $this->seedDraft();
    [$private, $privateManager, , $privatePicks] = $this->seedPrivateDraft();

    foreach (['picks/all', 'picks/selected'] as $route) {
      $this->assertSame([], $this->json($this->call('GET', "/draft/$draft/manager/$privateManager/$route")), $route);
    }

    $this->db()->update('draft', ['using_depth_charts' => 1], ['draft_id' => $draft]);
    static::getContainer()->get(DatabaseCacheService::class)->DeleteCachedItem("draft$draft");
    $chart = $this->json($this->call('GET', "/draft/$draft/manager/$privateManager/depth_chart"));
    $this->assertSame([], $chart['depthChartPositions'][0]['picks']);

    $own = $this->json($this->call('GET', "/draft/$private/manager/$privateManager/picks/selected", null, [self::PASSWORD_HEADER => 'secret']));
    $this->assertSame([1], array_column($own, 'player_pick'));
  }

  public function testSearchFindsMadePicksByNameTeamAndPosition(): void
  {
    [$draft] = $this->seedDraft();

    $loose = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Bro"));
    $this->assertSame(['Antonio'], array_column($loose['player_results'], 'first_name'));

    $team = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Jones&team=ATL&position=WR"));
    $this->assertSame(['Julio'], array_column($team['player_results'], 'first_name'));

    $wrongTeam = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Jones&team=PIT"));
    $this->assertSame([], $wrongTeam['player_results']);

    $ordered = $this->json($this->call('GET', "/draft/$draft/picks/search?position=WR&sort=ASC"));
    $this->assertSame(['Antonio', 'Julio'], array_column($ordered['player_results'], 'first_name'));
    $desc = $this->json($this->call('GET', "/draft/$draft/picks/search?position=WR&sort=nonsense"));
    $this->assertSame(['Julio', 'Antonio'], array_column($desc['player_results'], 'first_name'));
  }

  public function testSearchSplitsAFullNameWhenNothingElseMatches(): void
  {
    [$draft] = $this->seedDraft();

    $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode('Antonio Brown')));

    $this->assertSame(['Antonio'], array_column($result['player_results'], 'first_name'));
  }

  public function testSearchKeepsTheStrictResultsWhenThereAreSome(): void
  {
    [$draft] = $this->seedDraft();

    $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Brown"));

    $this->assertSame(['Antonio'], array_column($result['player_results'], 'first_name'));
    // The loose search scores its rows below zero, so a positive score means the ranked full-text query answered
    $this->assertGreaterThan(0, (float) $result['player_results'][0]['search_score']);
  }

  public function testSplitSearchHonorsTeamAndPosition(): void
  {
    [$draft, $a, $b] = $this->seedDraft();
    // Names too short for the full-text index, so only the split search can find them
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Al', 'last_name' => 'Be', 'team' => 'PIT', 'position' => 'RB'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'Al', 'last_name' => 'Xu', 'team' => 'ATL', 'position' => 'WR'], 4);
    $keywords = urlencode('Al Be');

    $both = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=$keywords"));
    $this->assertEqualsCanonicalizing(['Be', 'Xu'], array_column($both['player_results'], 'last_name'));

    $team = $this->call('GET', "/draft/$draft/picks/search?keywords=$keywords&team=PIT");
    $this->assertSame(200, $team->getStatusCode(), $team->getContent());
    $this->assertSame(['Be'], array_column($this->json($team)['player_results'], 'last_name'));

    $position = $this->call('GET', "/draft/$draft/picks/search?keywords=$keywords&team=ATL&position=WR");
    $this->assertSame(200, $position->getStatusCode(), $position->getContent());
    $this->assertSame(['Xu'], array_column($this->json($position)['player_results'], 'last_name'));
  }

  public function testManagerDepthChartGroupsPicksByPosition(): void
  {
    [$draft, $a, , $picks] = $this->seedDraft('in_progress', null, 1);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $draft, 'position' => 'WR', 'slots' => 2, 'display_order' => 1]);
    $position = (int) $this->db()->lastInsertId();
    $this->db()->update('players', ['depth_chart_position_id' => $position], ['player_id' => $picks[0]]);

    $response = $this->call('GET', "/draft/$draft/manager/$a/depth_chart");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertEquals($a, $body['manager_id']);
    $this->assertSame(['Unassigned', 'WR'], array_column($body['depthChartPositions'], 'position'));
    $this->assertSame([], $body['depthChartPositions'][0]['picks']);
    $this->assertSame(['Antonio'], array_column($body['depthChartPositions'][1]['picks'], 'first_name'));
    $this->assertEquals(2, $body['depthChartPositions'][1]['slots']);
  }

  public function testManagerDepthChartRequiresADraftThatUsesThem(): void
  {
    [$draft, $a] = $this->seedDraft();

    $response = $this->call('GET', "/draft/$draft/manager/$a/depth_chart");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['success' => false, 'errors' => ['Draft is not configured to use depth charts.']], $this->json($response));
  }

  public function testUpdateDepthChartAssignsAPosition(): void
  {
    [$draft, , , $picks] = $this->seedDraft('in_progress', null, 1);
    $this->db()->insert('depth_chart_positions', ['draft_id' => $draft, 'position' => 'WR', 'slots' => 2, 'display_order' => 1]);
    $position = (int) $this->db()->lastInsertId();

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/$position");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertTrue($this->json($response)['success']);
    $this->assertSame($position, (int) $this->db()->fetchOne('SELECT depth_chart_position_id FROM players WHERE player_id = ?', [$picks[0]]));
  }

  private function createPosition(int $draftId): int
  {
    $this->db()->insert('depth_chart_positions', ['draft_id' => $draftId, 'position' => 'WR', 'slots' => 2, 'display_order' => 1]);

    return (int) $this->db()->lastInsertId();
  }

  public function testUpdateDepthChartRejectsAPickFromAnotherDraftOrAMissingPick(): void
  {
    [$draft] = $this->seedDraft('in_progress', null, 1);
    $position = $this->createPosition($draft);
    $other = $this->createDraft((int) $this->db()->fetchOne("SELECT id FROM users WHERE email = ?", [self::EMAIL]));
    $foreign = $this->createPick($other, $this->createManager($other, 'Other', 1), 1, 1);

    $mismatch = $this->call('PUT', "/draft/$draft/pick/$foreign/depth_chart/$position");
    $this->assertSame(400, $mismatch->getStatusCode());
    $this->assertSame(["Pick does not belong to draft #$draft."], $this->json($mismatch)['errors']);
    $this->assertNull($this->db()->fetchOne('SELECT depth_chart_position_id FROM players WHERE player_id = ?', [$foreign]));

    $missing = $this->call('PUT', "/draft/$draft/pick/999999999/depth_chart/$position");
    $this->assertSame(400, $missing->getStatusCode());
    $this->assertSame(['Unable to edit pick #999999999'], $this->json($missing)['errors']);
  }

  public function testUpdateDepthChartStopsTenMinutesAfterTheDraftEnds(): void
  {
    [$recent, , , $recentPicks] = $this->seedDraft('complete', null, 1, gmdate('Y-m-d H:i:s', time() - 60));
    $position = $this->createPosition($recent);
    $this->assertSame(200, $this->call('PUT', "/draft/$recent/pick/{$recentPicks[0]}/depth_chart/$position")->getStatusCode());

    $this->cleanUp();

    [$old, , , $oldPicks] = $this->seedDraft('complete', null, 1, gmdate('Y-m-d H:i:s', time() - 3600));
    $late = $this->call('PUT', "/draft/$old/pick/{$oldPicks[0]}/depth_chart/1");
    $this->assertSame(400, $late->getStatusCode());
    $this->assertFalse($this->json($late)[0]['success']);
  }

  public function testUpdateDepthChartRejectsAPositionThatIsNotTheDraftsOwn(): void
  {
    [$draft, , , $picks] = $this->seedDraft('in_progress', null, 1);
    $this->createPosition($draft);
    $other = $this->createDraft((int) $this->db()->fetchOne("SELECT id FROM users WHERE email = ?", [self::EMAIL]), 'in_progress', null, 1);
    $foreignPosition = $this->createPosition($other);

    foreach ([$foreignPosition, 999999999] as $badPosition) {
      $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/$badPosition");

      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame(["Depth chart position #$badPosition does not belong to draft #$draft."], $this->json($response)['errors']);
    }

    $this->assertNull($this->db()->fetchOne('SELECT depth_chart_position_id FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testUpdateDepthChartRejectsADraftWithoutDepthCharts(): void
  {
    [$draft, , , $picks] = $this->seedDraft();

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/5");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(["Draft #$draft does not use depth charts."], $this->json($response)['errors']);
  }

  public function testUpdateDepthChartWithPositionZeroUnassignsThePick(): void
  {
    [$draft, , , $picks] = $this->seedDraft('in_progress', null, 1);
    $position = $this->createPosition($draft);
    $this->db()->update('players', ['depth_chart_position_id' => $position], ['player_id' => $picks[0]]);

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/0");

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertNull($this->db()->fetchOne('SELECT depth_chart_position_id FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testNonGetRequestsNeedTheRequestedWithHeader(): void
  {
    [$draft, , , $picks] = $this->seedDraft();

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/1", null, ['HTTP_X_REQUESTED_WITH' => '']);

    $this->assertSame(403, $response->getStatusCode());
  }
}
