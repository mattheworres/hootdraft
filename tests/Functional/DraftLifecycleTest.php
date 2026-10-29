<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** One draft taken from creation to completion through the HTTP API, the way the AngularJS client drives it. */
#[Group('database')]
class DraftLifecycleTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'lifecycle-test.example';
  }

  private function pickIds(int $draft): array
  {
    return array_map('intval', $this->db()->fetchFirstColumn('SELECT player_id FROM players WHERE draft_id = ? ORDER BY player_pick', [$draft]));
  }

  private function selectPlayer(int $draft, int $pickId, string $first, string $last, string $team, string $position): array
  {
    $response = $this->call('POST', "/commish/draft/$draft/pick/$pickId", ['first_name' => $first, 'last_name' => $last, 'team' => $team, 'position' => $position]);
    $this->assertSame(201, $response->getStatusCode(), $response->getContent());

    return $this->json($response);
  }

  public function testCreateStartTradePickAndComplete(): void
  {
    $alice = $this->createUser('Alice');
    $this->assertSame(200, $this->logIn('Alice')->getStatusCode());

    $created = $this->call('POST', '/commish/draft', ['name' => 'Lifecycle draft', 'sport' => 'NFL', 'style' => 'serpentine', 'rounds' => 2, 'password' => '', 'using_depth_charts' => false]);
    $this->assertSame(201, $created->getStatusCode(), $created->getContent());
    $draft = (int) $this->json($created)['draft']['draft_id'];
    $this->assertSame($alice, (int) $this->db()->fetchOne('SELECT commish_id FROM draft WHERE draft_id = ?', [$draft]));

    foreach (['Team A', 'Team B'] as $name) {
      $manager = $this->call('POST', "/commish/draft/$draft/manager", ['manager_name' => $name]);
      $this->assertSame(201, $manager->getStatusCode(), $manager->getContent());
    }
    [$a, $b] = array_map('intval', $this->db()->fetchFirstColumn('SELECT manager_id FROM managers WHERE draft_id = ? ORDER BY draft_order', [$draft]));

    // Picks and trades only exist once the draft is running
    $this->assertSame(400, $this->call('GET', "/commish/draft/$draft/pick/current")->getStatusCode());
    $start = $this->call('PUT', "/commish/draft/$draft/status", ['status' => 'in_progress']);
    $this->assertSame(200, $start->getStatusCode(), $start->getContent());
    $this->assertSame('in_progress', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$draft]));

    $picks = $this->pickIds($draft);
    $this->assertCount(4, $picks);
    $this->assertSame([$a, $b, $b, $a], array_map('intval', $this->db()->fetchFirstColumn('SELECT manager_id FROM players WHERE draft_id = ? ORDER BY player_pick', [$draft])));

    $this->selectPlayer($draft, $picks[0], 'Antonio', 'Brown', 'PIT', 'WR');

    // Swap Team B's second pick for Team A's last one
    $trade = $this->call('POST', "/commish/draft/$draft/trade", ['manager1_id' => $a, 'manager2_id' => $b, 'trade_assets' => [$picks[1], $picks[3]]]);
    $this->assertSame(201, $trade->getStatusCode(), $trade->getContent());
    $this->assertSame($a, (int) $this->db()->fetchOne('SELECT manager_id FROM players WHERE player_id = ?', [$picks[1]]));
    $this->assertSame($b, (int) $this->db()->fetchOne('SELECT manager_id FROM players WHERE player_id = ?', [$picks[3]]));

    // Everyone can follow along without a session
    $this->call('POST', '/logout');
    $this->assertCount(1, $this->json($this->call('GET', "/draft/$draft/trades")));
    $this->assertSame(200, $this->call('GET', "/draft/$draft/picks/next")->getStatusCode());
    $this->logIn('Alice');

    $this->selectPlayer($draft, $picks[1], 'Julio', 'Jones', 'ATL', 'WR');
    $this->selectPlayer($draft, $picks[2], 'Cam', 'Newton', 'CAR', 'QB');
    $this->assertSame('in_progress', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$draft]));

    $last = $this->selectPlayer($draft, $picks[3], 'Matt', 'Ryan', 'ATL', 'QB');
    $this->assertSame('complete', $this->db()->fetchOne('SELECT draft_status FROM draft WHERE draft_id = ?', [$draft]));
    $this->assertNotNull($last['draft_statistics']);

    $this->call('POST', '/logout');
    $stats = $this->call('GET', "/draft/$draft/stats");
    $this->assertSame(200, $stats->getStatusCode(), $stats->getContent());
    $this->assertSame('Atlanta Falcons', $this->json($stats)['draft_statistics']['most_drafted_team']);

    // A finished draft takes no more picks
    $this->logIn('Alice');
    $this->assertSame(400, $this->call('GET', "/commish/draft/$draft/pick/current")->getStatusCode());
  }
}
