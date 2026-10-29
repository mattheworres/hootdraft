<?php

namespace HootDraft\Tests\Functional;

use HootDraft\Domain\Services\DatabaseCacheService;
use PHPUnit\Framework\Attributes\Group;

/** Public pick reads, search and the depth chart (read and update), against the migrated test DB. */
#[Group('database')]
class PickTest extends CommishDraftTestCase
{
  private const PASSWORD_HEADER = 'HTTP_X_PHPDRAFT_DRAFTPASSWORD';
  private const EMAIL = 'alice@pick-test.example';

  /** A draft two picks in: round 2, pick 3 */
  private const PICKS_MADE = ['draft_current_round' => 2, 'draft_current_pick' => 3, 'draft_counter' => 2];

  protected static function domain(): string
  {
    return 'pick-test.example';
  }

  /** A second, private draft owned by the same commissioner. Returns [draft, manager A, manager B, pick ids]. */
  private function seedPrivateDraft(): array
  {
    $alice = (int) $this->db()->fetchOne('SELECT id FROM users WHERE email = ?', [self::EMAIL]);
    $draft = $this->createDraft($alice, 'in_progress', 'secret', self::PICKS_MADE);
    $a = $this->createManager($draft, 'Private A', 1);
    $b = $this->createManager($draft, 'Private B', 2);
    $picks = [
      $this->createPick($draft, $a, 1, 1, ['first_name' => 'Secret', 'last_name' => 'Player', 'team' => 'PIT', 'position' => 'WR'], 1),
      $this->createPick($draft, $b, 1, 2),
    ];

    return [$draft, $a, $b, $picks];
  }

  /** Two managers and four picks (round 1: A, B; round 2: B, A); the first two are made. Returns [draft, A, B, pick ids]. */
  private function seedMadeDraft(string $status = 'in_progress', ?string $password = null, int $depthCharts = 0, ?string $endTime = null): array
  {
    $alice = $this->createUser('Alice');
    $draft = $this->createDraft($alice, $status, $password, self::PICKS_MADE + ['using_depth_charts' => $depthCharts, 'draft_end_time' => $endTime]);
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

  public function testPicksAreHiddenUntilTheDraftHasStarted(): void
  {
    [$draft] = $this->seedMadeDraft('undrafted');

    $response = $this->call('GET', "/draft/$draft/picks");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->json($response)[0]['success']);
  }

  public function testPrivateDraftNeedsThePasswordHeader(): void
  {
    [$draft] = $this->seedMadeDraft('in_progress', 'secret');

    $blocked = $this->call('GET', "/draft/$draft/picks/last");
    $this->assertSame(400, $blocked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($blocked));

    $unlocked = $this->call('GET', "/draft/$draft/picks/last", null, [self::PASSWORD_HEADER => 'secret']);
    $this->assertSame(200, $unlocked->getStatusCode());
  }

  public function testGetAllGroupsPicksByRound(): void
  {
    [$draft] = $this->seedMadeDraft();

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
    [$draft] = $this->seedMadeDraft();

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
    [$draft] = $this->seedMadeDraft();

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
    [$draft, $a, $b] = $this->seedMadeDraft();

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
    [$draft] = $this->seedMadeDraft();
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
    [$draft] = $this->seedMadeDraft();

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

  public function testSearchFindsAPartialNameInsideAName(): void
  {
    [$draft, $a] = $this->seedMadeDraft();
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'LaDainian', 'last_name' => 'Tomlinson', 'team' => 'SD', 'position' => 'RB'], 3);

    $prefix = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Tom"));
    $this->assertSame(['Tomlinson'], array_column($prefix['player_results'], 'last_name'));

    $inside = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=linson"));
    $this->assertSame(['Tomlinson'], array_column($inside['player_results'], 'last_name'));
  }

  public function testSearchRanksTheBestMatchFirstWhateverThePickOrder(): void
  {
    [$draft, $a, $b] = $this->seedMadeDraft();
    // Picks 1 and 2 are made already. Pick 5 is the exact name, pick 6 starts with it, pick 7 only contains it.
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Tom', 'last_name' => 'Brady', 'team' => 'NE', 'position' => 'QB'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'LaDainian', 'last_name' => 'Tomlinson', 'team' => 'SD', 'position' => 'RB'], 4);
    $this->createPick($draft, $a, 4, 7, ['first_name' => 'Autumn', 'last_name' => 'Bartomeo', 'team' => 'DAL', 'position' => 'WR'], 5);

    foreach (['DESC', 'ASC'] as $sort) {
      $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Tom&sort=$sort"));
      $this->assertSame(['Brady', 'Tomlinson', 'Bartomeo'], array_column($result['player_results'], 'last_name'), "tiers rank before pick order ($sort)");
    }
  }

  public function testSearchSortToggleOrdersPicksInsideOneTier(): void
  {
    [$draft, $a, $b] = $this->seedMadeDraft();
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Tom', 'last_name' => 'Brady', 'team' => 'NE', 'position' => 'QB'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'Tom', 'last_name' => 'Cruise', 'team' => 'NE', 'position' => 'QB'], 4);

    $asc = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Tom&sort=ASC"));
    $this->assertSame(['Brady', 'Cruise'], array_column($asc['player_results'], 'last_name'));

    $desc = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Tom&sort=nonsense"));
    $this->assertSame(['Cruise', 'Brady'], array_column($desc['player_results'], 'last_name'));
  }

  public function testSearchFindsAFullNameInEitherOrder(): void
  {
    [$draft] = $this->seedMadeDraft();

    foreach (['Antonio Brown', 'Brown Antonio', 'ant brow'] as $keywords) {
      $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode($keywords)));
      $this->assertSame(['Antonio'], array_column($result['player_results'], 'first_name'), $keywords);
    }

    $none = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode('Antonio Jones')));
    $this->assertSame([], $none['player_results']);
  }

  public function testSearchFullNameOutranksAPartialMatch(): void
  {
    [$draft, $a] = $this->seedMadeDraft();
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Antonio', 'last_name' => 'Browning', 'team' => 'PIT', 'position' => 'WR'], 3);

    $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode('Antonio Brown') . '&sort=DESC'));

    $this->assertSame(['Brown', 'Browning'], array_column($result['player_results'], 'last_name'));
    $this->assertGreaterThan($result['player_results'][1]['search_score'], $result['player_results'][0]['search_score']);
  }

  public function testSearchHonorsTeamAndPositionWithAFullName(): void
  {
    [$draft, $a, $b] = $this->seedMadeDraft();
    // Names of two letters, which a full-text index would skip
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Al', 'last_name' => 'Be', 'team' => 'PIT', 'position' => 'RB'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'Al', 'last_name' => 'Xu', 'team' => 'ATL', 'position' => 'WR'], 4);

    $both = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Al"));
    $this->assertEqualsCanonicalizing(['Be', 'Xu'], array_column($both['player_results'], 'last_name'));

    $team = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode('Al Be') . '&team=PIT'));
    $this->assertSame(['Be'], array_column($team['player_results'], 'last_name'));

    $position = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=Al&team=ATL&position=WR"));
    $this->assertSame(['Xu'], array_column($position['player_results'], 'last_name'));
  }

  public function testSearchTreatsLikeWildcardsAsPlainCharacters(): void
  {
    [$draft, $a] = $this->seedMadeDraft();
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Pat', 'last_name' => '50%_Off', 'team' => 'PIT', 'position' => 'RB'], 3);

    foreach (['%', '_', 'Bro_n'] as $keywords) {
      $expected = $keywords == 'Bro_n' ? [] : ['50%_Off'];
      $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode($keywords)));
      $this->assertSame($expected, array_column($result['player_results'], 'last_name'), $keywords);
    }
  }

  public function testSearchWithoutKeywordsReturnsAllSelectedPicksInSortOrder(): void
  {
    [$draft] = $this->seedMadeDraft();

    $asc = $this->json($this->call('GET', "/draft/$draft/picks/search?sort=ASC"));
    $this->assertSame(['Antonio', 'Julio'], array_column($asc['player_results'], 'first_name'));

    $desc = $this->json($this->call('GET', "/draft/$draft/picks/search?sort=nonsense"));
    $this->assertSame(['Julio', 'Antonio'], array_column($desc['player_results'], 'first_name'));
  }

  public function testSearchRanksMultipleWordsByTheirWorstWord(): void
  {
    [$draft, $a, $b] = $this->seedMadeDraft();
    // "Tom" is only inside "Bartom", so the earlier pick ranks below the later one.
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Bartom', 'last_name' => 'Bradley', 'team' => 'DAL', 'position' => 'WR'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'Tom', 'last_name' => 'Bradley', 'team' => 'NE', 'position' => 'QB'], 4);

    $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode('Tom Brad') . '&sort=ASC'));

    $this->assertSame(['Tom', 'Bartom'], array_column($result['player_results'], 'first_name'));
    $this->assertGreaterThan($result['player_results'][1]['search_score'], $result['player_results'][0]['search_score']);
  }

  public function testSearchTreatsABackslashAsAPlainCharacter(): void
  {
    [$draft, $a, $b] = $this->seedMadeDraft();
    $this->createPick($draft, $a, 3, 5, ['first_name' => 'Pat', 'last_name' => 'A\\B', 'team' => 'PIT', 'position' => 'RB'], 3);
    $this->createPick($draft, $b, 3, 6, ['first_name' => 'Sam', 'last_name' => 'AB', 'team' => 'PIT', 'position' => 'RB'], 4);

    foreach (['A\\B' => ['A\\B'], '\\' => ['A\\B'], 'B\\' => []] as $keywords => $expected) {
      $result = $this->json($this->call('GET', "/draft/$draft/picks/search?keywords=" . urlencode((string) $keywords)));
      $this->assertSame($expected, array_column($result['player_results'], 'last_name'), (string) $keywords);
    }
  }

  public function testManagerDepthChartGroupsPicksByPosition(): void
  {
    [$draft, $a, , $picks] = $this->seedMadeDraft('in_progress', null, 1);
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
    [$draft, $a] = $this->seedMadeDraft();

    $response = $this->call('GET', "/draft/$draft/manager/$a/depth_chart");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(['success' => false, 'errors' => ['Draft is not configured to use depth charts.']], $this->json($response));
  }

  public function testUpdateDepthChartAssignsAPosition(): void
  {
    [$draft, , , $picks] = $this->seedMadeDraft('in_progress', null, 1);
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
    [$draft] = $this->seedMadeDraft('in_progress', null, 1);
    $position = $this->createPosition($draft);
    $other = $this->createDraft((int) $this->db()->fetchOne("SELECT id FROM users WHERE email = ?", [self::EMAIL]), 'in_progress', null, self::PICKS_MADE);
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
    [$recent, , , $recentPicks] = $this->seedMadeDraft('complete', null, 1, gmdate('Y-m-d H:i:s', time() - 60));
    $position = $this->createPosition($recent);
    $this->assertSame(200, $this->call('PUT', "/draft/$recent/pick/{$recentPicks[0]}/depth_chart/$position")->getStatusCode());

    $this->cleanUp();

    [$old, , , $oldPicks] = $this->seedMadeDraft('complete', null, 1, gmdate('Y-m-d H:i:s', time() - 3600));
    $late = $this->call('PUT', "/draft/$old/pick/{$oldPicks[0]}/depth_chart/1");
    $this->assertSame(400, $late->getStatusCode());
    $this->assertFalse($this->json($late)[0]['success']);
  }

  public function testUpdateDepthChartRejectsAPositionThatIsNotTheDraftsOwn(): void
  {
    [$draft, , , $picks] = $this->seedMadeDraft('in_progress', null, 1);
    $this->createPosition($draft);
    $other = $this->createDraft((int) $this->db()->fetchOne("SELECT id FROM users WHERE email = ?", [self::EMAIL]), 'in_progress', null, self::PICKS_MADE + ['using_depth_charts' => 1]);
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
    [$draft, , , $picks] = $this->seedMadeDraft();

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/5");

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(["Draft #$draft does not use depth charts."], $this->json($response)['errors']);
  }

  public function testUpdateDepthChartWithPositionZeroUnassignsThePick(): void
  {
    [$draft, , , $picks] = $this->seedMadeDraft('in_progress', null, 1);
    $position = $this->createPosition($draft);
    $this->db()->update('players', ['depth_chart_position_id' => $position], ['player_id' => $picks[0]]);

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/0");

    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $this->assertNull($this->db()->fetchOne('SELECT depth_chart_position_id FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testNonGetRequestsNeedTheRequestedWithHeader(): void
  {
    [$draft, , , $picks] = $this->seedMadeDraft();

    $response = $this->call('PUT', "/draft/$draft/pick/{$picks[0]}/depth_chart/1", null, ['HTTP_X_REQUESTED_WITH' => '']);

    $this->assertSame(403, $response->getStatusCode());
  }
}
