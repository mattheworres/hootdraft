<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** The public stats read (/draft/{id}/stats). */
#[Group('database')]
class DraftStatsTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'draft-stats-test.example';
  }

  public function testStatsAreNullUntilTheyHaveBeenCalculated(): void
  {
    [$draft] = $this->seedDraft('complete');

    $response = $this->call('GET', "/draft/$draft/stats");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertNull($body['draft_statistics']);
  }

  public function testCompletingADraftCalculatesStatsThatAnyoneCanRead(): void
  {
    [$draft, , , $picks] = $this->seedDraft();
    $this->logIn('Alice');

    foreach ([['Antonio', 'Brown', 'PIT', 'WR'], ['Julio', 'Jones', 'ATL', 'WR'], ['Cam', 'Newton', 'CAR', 'QB'], ['Matt', 'Ryan', 'ATL', 'QB']] as $i => [$first, $last, $team, $position]) {
      $this->call('POST', "/commish/draft/$draft/pick/{$picks[$i]}", ['first_name' => $first, 'last_name' => $last, 'team' => $team, 'position' => $position]);
    }
    $this->call('POST', '/logout');

    $body = $this->json($this->call('GET', "/draft/$draft/stats"));
    $stats = $body['draft_statistics'];

    $this->assertTrue($body['success']);
    $this->assertSame($draft, (int) $stats['draft_id']);
    $this->assertSame('Atlanta Falcons', $stats['most_drafted_team']);
    $this->assertSame(2, $stats['most_drafted_team_count']);
    $this->assertSame(2, $stats['most_drafted_position_count']);
    $this->assertIsInt($stats['average_pick_seconds']);
    $this->assertContains($stats['longest_avg_pick_manager_name'], ['Team A', 'Team B']);
  }

  public function testPrivateDraftStatsNeedThePasswordOrTheOwner(): void
  {
    [$draft] = $this->seedDraft('complete', 'hunter2');

    $locked = $this->call('GET', "/draft/$draft/stats");
    $this->assertSame(400, $locked->getStatusCode());
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($locked));

    $this->logIn('Alice');
    $this->assertSame(200, $this->call('GET', "/draft/$draft/stats")->getStatusCode());
  }
}
