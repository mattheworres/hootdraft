<?php

namespace HootDraft\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/** Trades: the public list (/draft/{id}/trades) and the commissioner's asset lookup and trade entry. */
#[Group('database')]
class TradeTest extends CommishDraftTestCase
{
  protected static function domain(): string
  {
    return 'trade-test.example';
  }

  private function tradeBody(int $a, int $b, array $assets): array
  {
    return ['manager1_id' => $a, 'manager2_id' => $b, 'trade_assets' => $assets];
  }

  /** Marks a pick as made, as if the draft had moved on to the next one. */
  private function madePick(int $draft, int $pickId): void
  {
    $this->db()->update('players', ['first_name' => 'Antonio', 'last_name' => 'Brown', 'team' => 'PIT', 'position' => 'WR', 'pick_time' => '2024-01-01 12:05:00', 'pick_duration' => 300, 'player_counter' => 1], ['player_id' => $pickId]);
    $this->db()->update('draft', ['draft_current_pick' => 2, 'draft_counter' => 1], ['draft_id' => $draft]);
  }

  public function testCommishTradeRoutesNeedASessionAndTheirOwnDraft(): void
  {
    [$draft, $a, $b, $picks] = $this->seedDraft();

    $this->assertSame(401, $this->call('GET', "/commish/draft/$draft/manager/$a/assets")->getStatusCode());
    $this->assertSame(401, $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0]]))->getStatusCode());

    $this->createUser('Bob');
    $this->logIn('Bob');

    foreach ([['GET', "/commish/draft/$draft/manager/$a/assets", null], ['POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0]])]] as [$method, $uri, $body]) {
      $response = $this->call($method, $uri, $body);
      $this->assertSame(400, $response->getStatusCode(), $uri);
      $this->assertSame([['success' => false, 'errors' => ['You do not have permission to this draft.']]], $this->json($response), $uri);
    }

    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM trades WHERE draft_id = ?', [$draft]));
  }

  public function testCommishTradeRoutesNeedADraftInProgress(): void
  {
    [$draft, $a, $b, $picks] = $this->seedDraft('undrafted');
    $this->logIn('Alice');

    $this->assertSame(400, $this->call('GET', "/commish/draft/$draft/manager/$a/assets")->getStatusCode());
    $this->assertSame(400, $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0]]))->getStatusCode());
  }

  public function testGetAssetsListsAManagersPicks(): void
  {
    [$draft, $a, , $picks] = $this->seedDraft();
    $this->madePick($draft, $picks[0]);
    $this->logIn('Alice');

    $response = $this->call('GET', "/commish/draft/$draft/manager/$a/assets");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $this->assertSame($a, (int) $body['manager_id']);
    $this->assertSame([$picks[0], $picks[3]], array_map('intval', array_column($body['assets'], 'player_id')));
    $this->assertTrue($body['assets'][0]['selected']);
    $this->assertFalse($body['assets'][1]['selected']);
    $this->assertFalse($body['assets'][1]['on_the_clock']);
  }

  public function testGetAssetsRejectsAManagerFromAnotherDraft(): void
  {
    [$draft] = $this->seedDraft();
    [, $foreignManager] = $this->seedDraft();
    $this->logIn('Alice');

    $foreign = $this->call('GET', "/commish/draft/$draft/manager/$foreignManager/assets");
    $this->assertSame(400, $foreign->getStatusCode());
    $this->assertSame(["Manager does not belong to draft #$draft"], $this->json($foreign)['errors']);

    $unknown = $this->call('GET', "/commish/draft/$draft/manager/999999999/assets");
    $this->assertSame(400, $unknown->getStatusCode());
    $this->assertSame(['Unable to load manager #999999999'], $this->json($unknown)['errors']);
  }

  public function testCreateTradeSwapsThePicksAndRecordsTheAssets(): void
  {
    [$draft, $a, $b, $picks] = $this->seedDraft();
    $this->madePick($draft, $picks[0]);
    $this->logIn('Alice');

    $response = $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0], $picks[2]]));
    $this->assertSame(201, $response->getStatusCode(), $response->getContent());
    $body = $this->json($response);

    $this->assertTrue($body['success']);
    $trade = $body['trade'];
    $this->assertSame($draft, (int) $trade['draft_id']);
    $this->assertSame(1, (int) $trade['trade_round']);
    $this->assertCount(2, $trade['trade_assets']);

    $this->assertSame($b, (int) $this->db()->fetchOne('SELECT manager_id FROM players WHERE player_id = ?', [$picks[0]]));
    $this->assertSame($a, (int) $this->db()->fetchOne('SELECT manager_id FROM players WHERE player_id = ?', [$picks[2]]));
    $this->assertSame(2, (int) $this->db()->fetchOne('SELECT player_counter FROM players WHERE player_id = ?', [$picks[0]]));
    $this->assertSame(2, (int) $this->db()->fetchOne('SELECT draft_counter FROM draft WHERE draft_id = ?', [$draft]));

    $assets = $this->db()->fetchAllAssociative('SELECT * FROM trade_assets WHERE trade_id = ? ORDER BY player_id', [$trade['trade_id']]);
    $this->assertSame([$picks[0], $picks[2]], array_map(fn($row) => (int) $row['player_id'], $assets));
    $this->assertSame([[$a, $b, 1], [$b, $a, 0]], array_map(fn($row) => [(int) $row['oldmanager_id'], (int) $row['newmanager_id'], (int) $row['was_drafted']], $assets));
  }

  public function testCreateTradeValidation(): void
  {
    [$draft, $a, $b, $picks] = $this->seedDraft();
    [$otherDraft, $otherA, , $otherPicks] = $this->seedDraft();
    $this->logIn('Alice');

    $noAssets = $this->call('POST', "/commish/draft/$draft/trade", ['manager1_id' => $a, 'manager2_id' => $b]);
    $this->assertSame(400, $noAssets->getStatusCode());
    $this->assertSame(['Unable to build trade: Invalid trade data.'], $this->json($noAssets)['errors']);

    $unknownManager = $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, 999999999, [$picks[0]]));
    $this->assertSame(400, $unknownManager->getStatusCode());
    $this->assertStringStartsWith('Unable to build trade:', $this->json($unknownManager)['errors'][0]);

    $unrelatedPick = $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$otherPicks[0]]));
    $this->assertSame(['Unable to build trade: Invalid trade data.'], $this->json($unrelatedPick)['errors']);

    // The other draft's manager and pick line up with each other but not with this draft
    $foreign = $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $otherA, [$picks[0], $otherPicks[0]]));
    $this->assertSame(400, $foreign->getStatusCode());
    $this->assertEqualsCanonicalizing(["Asset #{$otherPicks[0]} does not belong to draft #$draft", "Manager 2 does not belong to draft #$draft"], $this->json($foreign)['errors']);

    $duplicate = $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0], $picks[0]]));
    $this->assertSame(['One or more of the trade assets are duplicate.'], $this->json($duplicate)['errors']);

    $this->assertSame(0, (int) $this->db()->fetchOne('SELECT COUNT(*) FROM trades WHERE draft_id IN (?, ?)', [$draft, $otherDraft]));
    $this->assertSame($a, (int) $this->db()->fetchOne('SELECT manager_id FROM players WHERE player_id = ?', [$picks[0]]));
  }

  public function testTradesArePublicOnceTheDraftHasStarted(): void
  {
    [$draft, $a, $b, $picks] = $this->seedDraft();
    $this->logIn('Alice');
    $this->call('POST', "/commish/draft/$draft/trade", $this->tradeBody($a, $b, [$picks[0]]));
    $this->call('POST', '/logout');

    $response = $this->call('GET', "/draft/$draft/trades");
    $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    $trades = $this->json($response);

    $this->assertCount(1, $trades);
    $this->assertSame('Team A', $trades[0]['manager1']['manager_name']);
    $this->assertSame('Team B', $trades[0]['manager2']['manager_name']);
    $this->assertStringEndsWith(' UTC', $trades[0]['trade_time']);
    $this->assertCount(1, $trades[0]['trade_assets']);
    $asset = $trades[0]['trade_assets'][0];
    $this->assertSame($picks[0], (int) $asset['player']['player_id']);
    $this->assertSame('Team A', $asset['oldmanager']['manager_name']);
    $this->assertSame('Team B', $asset['newmanager']['manager_name']);
  }

  public function testTradesFollowTheDraftChecks(): void
  {
    [$undrafted] = $this->seedDraft('undrafted');
    $response = $this->call('GET', "/draft/$undrafted/trades");
    $this->assertSame(400, $response->getStatusCode());
    $this->assertFalse($this->json($response)[0]['success']);

    [$private] = $this->seedDraft('in_progress', 'secret');
    $response = $this->call('GET', "/draft/$private/trades");
    $this->assertSame([['success' => false, 'errors' => ['Draft marked as private.']]], $this->json($response));
  }
}
