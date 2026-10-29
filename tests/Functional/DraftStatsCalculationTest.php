<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** What POST /admin/draft/{id}/stats calculates, with pick durations seeded straight into the database. */
#[Group('database')]
class DraftStatsCalculationTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'draft-stats-calc-test.example';
  }

  /** @param array[] $picks pick id index (0 to 3) => [team, position, duration] */
  private function calculate(array $picks, string $end = '2024-01-01 12:30:00'): array
  {
    [$draft, , , $ids] = $this->seedDraft('complete');
    foreach ($picks as $i => [$team, $position, $duration]) {
      $this->db()->update('players', [
        'first_name' => 'F', 'last_name' => 'L', 'team' => $team, 'position' => $position, 'pick_time' => '2024-01-01 12:05:00',
        'pick_duration' => $duration, 'player_counter' => $i + 1,
      ], ['player_id' => $ids[$i]]);
    }
    $this->db()->update('draft', ['draft_end_time' => $end], ['draft_id' => $draft]);

    $this->createUser('Root', 'ROLE_ADMIN');
    $this->logIn('Root');

    $response = $this->call('POST', "/admin/draft/$draft/stats");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());

    return $this->json($response);
  }

  public function testManagerSuperlativesAreReadFromThePerManagerAggregates(): void
  {
    // Picks are Team A, Team B, Team B, Team A: A takes 30 and 50 seconds, B takes 100 and 200
    $stats = $this->calculate([['ATL', 'WR', 30], ['ATL', 'WR', 100], ['ATL', 'WR', 200], ['ATL', 'WR', 50]]);

    $this->assertSame(['Team B', 150], [$stats['longest_avg_pick_manager_name'], $stats['longest_avg_pick_seconds']]);
    $this->assertSame(['Team A', 40], [$stats['shortest_avg_pick_manager_name'], $stats['shortest_avg_pick_seconds']]);
    $this->assertSame(['Team B', 200], [$stats['longest_single_pick_manager_name'], $stats['longest_single_pick_seconds']]);
    $this->assertSame(['Team A', 30], [$stats['shortest_single_pick_manager_name'], $stats['shortest_single_pick_seconds']]);
    $this->assertSame(95, $stats['average_pick_seconds']);
  }

  public function testRoundSuperlativesAndAverage(): void
  {
    // Round 1 takes 30 + 100 seconds, round 2 takes 200 + 50
    $stats = $this->calculate([['ATL', 'WR', 30], ['ATL', 'WR', 100], ['ATL', 'WR', 200], ['ATL', 'WR', 50]]);

    $this->assertSame([2, 250], [$stats['longest_round'], $stats['longest_round_seconds']]);
    $this->assertSame([1, 130], [$stats['shortest_round'], $stats['shortest_round_seconds']]);
    $this->assertSame(190, $stats['average_round_seconds']);
    $this->assertSame(1800, $stats['drafting_time_seconds']);
  }

  public function testTeamAndPositionSuperlativesFallBackToTheCodeForUnknownValues(): void
  {
    $stats = $this->calculate([['ATL', 'WR', 10], ['ATL', 'WR', 10], ['ATL', 'WR', 10], ['XYZ', 'ZZ', 10]]);

    $this->assertSame(['Atlanta Falcons', 3], [$stats['most_drafted_team'], $stats['most_drafted_team_count']]);
    $this->assertSame(['XYZ', 1], [$stats['least_drafted_team'], $stats['least_drafted_team_count']]);
    $this->assertSame(['Wide Receiver', 3], [$stats['most_drafted_position'], $stats['most_drafted_position_count']]);
    $this->assertSame(['ZZ', 1], [$stats['least_drafted_position'], $stats['least_drafted_position_count']]);
  }

  public function testADraftWithNoPicksMadeHasEmptyStats(): void
  {
    $stats = $this->calculate([]);

    $this->assertNull($stats['longest_avg_pick_manager_name']);
    $this->assertSame(0, $stats['longest_avg_pick_seconds']);
    $this->assertSame(0, $stats['average_pick_seconds']);
    $this->assertSame(0, $stats['longest_round']);
    $this->assertSame('', $stats['most_drafted_team']);
    $this->assertSame(0, $stats['most_drafted_team_count']);
    $this->assertSame('', $stats['least_drafted_position']);
  }

  public function testEveryStatIsStoredAndReadBackAsTheCalculatedValue(): void
  {
    $calculated = $this->calculate([['PIT', 'WR', 30], ['ATL', 'QB', 100], ['ATL', 'QB', 200], ['PIT', 'WR', 50]]);
    $draft = (int) $calculated['draft_id'];

    $read = $this->json($this->call('GET', "/draft/$draft/stats"))['draft_statistics'];

    $this->assertEquals($calculated, $read);
    $this->assertSame($calculated['draft_stat_id'], (int) $this->db()->fetchOne('SELECT draft_stat_id FROM draft_stats WHERE draft_id = ?', [$draft]));
  }
}
